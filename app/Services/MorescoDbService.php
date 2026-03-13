<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MorescoDbService
{
    /**
     * Fetch members from the MORESCO SQL Server vw_members_list view.
     *
     * @param string|null $search  Optional search string (matches name or phone)
     * @param int         $perPage Number of records to return (default 100)
     * @param int         $offset  Offset for pagination
     * @return array
     */
    public function getMembers(?string $search = null, int $perPage = 100, int $offset = 0): array
    {
        try {
            $pdo = $this->getConnection();

            $whereClause = "WHERE ContactNo is not NULL and ContactNo <> ''";
            $params = [];

            if ($search && strlen($search) >= 1) {
                // PDO ODBC cannot bind ? params for LIKE against SQL Server nvarchar columns
                // (causes SQLSTATE[22018]). Safely escape and inline the value instead.
                $escaped = str_replace("'", "''", $search);
                $like    = "'%" . $escaped . "%'";
                $whereClause = "WHERE (ContactNo is not NULL and ContactNo <> '')
                    AND (MemberName LIKE {$like}
                      OR ContactNo  LIKE {$like}
                      OR member_ID  LIKE {$like})";
                $params = [];
            }

            $sql = "
                SELECT
                    member_ID,
                    MemberName,
                    ContactNo,
                    email,
                    Address,
                    service_area,
                    sa_code,
                    membershipstatus,
                    Municipality,
                    Barangay
                FROM dbo.vw_members_list
                {$whereClause}
                ORDER BY MemberName
                OFFSET {$offset} ROWS FETCH NEXT {$perPage} ROWS ONLY
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(fn($row) => $this->mapMember($row), $rows);

        } catch (\Exception $e) {
            Log::error('MorescoDbService::getMembers failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count all members (with optional search).
     */
    public function countMembers(?string $search = null): int
    {
        try {
            $pdo = $this->getConnection();

            $whereClause = '';
            $params = [];

            if ($search && strlen($search) >= 1) {
                $escaped = str_replace("'", "''", $search);
                $like    = "'%" . $escaped . "%'";
                $whereClause = "WHERE (MemberName LIKE {$like} OR ContactNo LIKE {$like} OR email LIKE {$like})";
                $params = [];
            }

            $sql = "SELECT COUNT(*) AS total FROM dbo.vw_members_list {$whereClause}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return (int) ($result['total'] ?? 0);

        } catch (\Exception $e) {
            Log::error('MorescoDbService::countMembers failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Look up a single member by their member_ID / account number.
     * Returns a mapped member array, or null if not found.
     */
    public function getMemberByAccountNumber(string $accountNo): ?array
    {
        try {
            $pdo = $this->getConnection();
            $escaped = trim($accountNo);

            // 1. Resolve member_id from dbo.account
            $stmtMap = $pdo->prepare("SELECT member_id FROM dbo.account WHERE account_no = ?");
            $stmtMap->execute([$escaped]);
            $accountRecord = $stmtMap->fetch(\PDO::FETCH_ASSOC);

            if (!$accountRecord) {
                return null;
            }

            // 2. Query vw_members_list using the resolved member_id
            $sql = "
                SELECT
                    member_ID, MemberName, ContactNo, email,
                    Address, service_area, sa_code, membershipstatus, Municipality, Barangay
                FROM dbo.vw_members_list
                WHERE member_ID = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$accountRecord['member_id']]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $row ? $this->mapMember($row) : null;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MorescoDbService::getMemberByAccountNumber failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get member details by Member ID directly from vw_members_list
     */
    public function getMemberById(string $memberId): ?array
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("SELECT TOP 1 * FROM dbo.vw_members_list WHERE member_ID = ?");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$member) {
                return null;
            }

            return [
                'id'           => $this->toUtf8($member['member_ID']),
                'name'         => $this->toUtf8($member['MemberName'] ?? ''),
                'sa_code'      => $this->toUtf8($member['sa_code'] ?? ''),
                'status'       => $this->toUtf8($member['membershipstatus'] ?? ''),
                'address'      => $this->toUtf8($member['service_area'] ?? ''),
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MorescoDbService::getMemberById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get billing / payment data for a member by account number.
     * Queries dbo.vw_AccountTransactions (from bill_ledger) to return:
     *   bill_amount       — latest debit (charge) amount
     *   billing_period    — month/year of the latest bill
     *   due_date          — estimated due date (15th of billing month)
     *   balance           — current running balance
     *   last_payment_amount — most recent credit (payment) amount
     *   last_payment_date   — date of most recent payment
     *   or_number           — Official Receipt of most recent payment
     */
    public function getMemberBillingData(string $accountNo): array
    {
        $empty = [
            'bill_amount'          => 'N/A',
            'billing_period'       => 'N/A',
            'due_date'             => 'N/A',
            'balance'              => 'N/A',
            'last_payment_amount'  => 'N/A',
            'last_payment_date'    => 'N/A',
            'or_number'            => 'N/A',
            'account_status'       => 'N/A',
        ];

        try {
            $pdo     = $this->getConnection();
            $escaped = trim($accountNo);

            // ── Latest account info (due date, status) ──────────────────────
            $stmtAcc = $pdo->prepare("
                SELECT TOP 1
                    due_date,
                    status_id
                FROM dbo.VW_ACCOUNTS_METER_READING
                WHERE account_no = ?
                ORDER BY billmo DESC, rdng_date DESC
            ");
            $stmtAcc->execute([$escaped]);
            $acc = $stmtAcc->fetch(\PDO::FETCH_ASSOC);

            // ── Latest bill (credit row with no OR) ─────────────────────────
            $stmtBill = $pdo->prepare("
                SELECT TOP 1
                    credit      AS bill_amount,
                    balance     AS balance,
                    trans_date  AS bill_date
                FROM dbo.vw_AccountTransactions
                WHERE account_no = ?
                  AND credit > 0
                  AND [official_receipt] IS NULL
                  AND (isReversed IS NULL OR isReversed = 0)
                ORDER BY trans_date DESC
            ");
            $stmtBill->execute([$escaped]);
            $bill = $stmtBill->fetch(\PDO::FETCH_ASSOC);

            // ── Latest payment (debit row with OR) ────────────────────────
            $stmtPay = $pdo->prepare("
                SELECT TOP 1
                    debit               AS payment_amount,
                    trans_date          AS payment_date,
                    [official_receipt]  AS or_number
                FROM dbo.vw_AccountTransactions
                WHERE account_no = ?
                  AND debit > 0
                  AND [official_receipt] IS NOT NULL
                  AND (isReversed IS NULL OR isReversed = 0)
                ORDER BY trans_date DESC
            ");
            $stmtPay->execute([$escaped]);
            $pay = $stmtPay->fetch(\PDO::FETCH_ASSOC);

            if (!$bill && !$pay) {
                return $empty;
            }

            // ── Account running balance ───────────────────────────────────────
            $stmtBal = $pdo->prepare("
                SELECT SUM(credit) - SUM(debit) AS true_balance
                FROM dbo.vw_AccountTransactions
                WHERE account_no = ?
                  AND (isReversed IS NULL OR isReversed = 0)
            ");
            $stmtBal->execute([$escaped]);
            $balRow = $stmtBal->fetch(\PDO::FETCH_ASSOC);
            $trueBalance = (float)($balRow['true_balance'] ?? 0);

            // ── Format bill fields ────────────────────────────────────────────
            $billAmount     = 'N/A';
            $billingPeriod  = 'N/A';
            $dueDate        = 'N/A';
            $balance        = '₱' . number_format($trueBalance, 2);

            if ($bill) {
                $billAmount    = '₱' . number_format((float)($bill['bill_amount'] ?? 0), 2);
                $billDateRaw   = $bill['bill_date'] ?? null;
                if ($billDateRaw) {
                    try {
                        $dt            = new \DateTime($billDateRaw);
                        $billingPeriod = $dt->format('F Y');           // e.g. "February 2026"
                        $dueDate       = $dt->format('F') . ' 15, ' . $dt->format('Y'); // e.g. "February 15, 2026"
                    } catch (\Exception $ignored) {}
                }
            }

            // ── Format payment fields ─────────────────────────────────────────
            $lastPaymentAmount = 'N/A';
            $lastPaymentDate   = 'N/A';
            $orNumber          = 'N/A';
            $accountStatus     = 'N/A';

            if ($pay) {
                $lastPaymentAmount = '₱' . number_format((float)($pay['payment_amount'] ?? 0), 2);
                $payDateRaw        = $pay['payment_date'] ?? null;
                if ($payDateRaw) {
                    try {
                        $lastPaymentDate = (new \DateTime($payDateRaw))->format('F d, Y');
                    } catch (\Exception $ignored) {}
                }
                $orNumber = $this->toUtf8(trim($pay['or_number'] ?? 'N/A'));
            }

            // ── Account status from VW_ACCOUNTS_METER_READING ────────────────
            if ($acc) {
                // Use the real due date if available
                $accDueDateRaw = $acc['due_date'] ?? null;
                if ($accDueDateRaw) {
                    try {
                        $dueDate = (new \DateTime($accDueDateRaw))->format('F d, Y');
                    } catch (\Exception $ignored) {}
                }

                // Map status_id to a readable string if possible
                // For now, we'll return it as-is or map common ones if known.
                // If status_id is just a number, we'll label it.
                $sid = $acc['status_id'] ?? null;
                if ($sid !== null) {
                    $accountStatus = match((int)$sid) {
                        1       => 'Active',
                        2       => 'Disconnected',
                        3       => 'For Disconnection',
                        default => 'Status ' . $sid
                    };
                }
            }

            return [
                'bill_amount'          => $billAmount,
                'billing_period'       => $billingPeriod,
                'due_date'             => $dueDate,
                'balance'              => $balance,
                'last_payment_amount'  => $lastPaymentAmount,
                'last_payment_date'    => $lastPaymentDate,
                'or_number'            => $orNumber,
                'account_status'       => $accountStatus,
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MorescoDbService::getMemberBillingData failed: ' . $e->getMessage());
            return $empty;
        }
    }

    /**
     * Get distinct service areas with member counts — used as "groups" in the broadcast picker.
     * Returns array of: [ id (sa_code), name, member_count ]
     */
    public function getServiceAreaGroups(): array
    {
        try {
            $pdo = $this->getConnection();

            $sql = "
                SELECT
                    sa_code,
                    service_area,
                    COUNT(*) AS member_count
                FROM dbo.vw_members_list
                WHERE ContactNo IS NOT NULL AND ContactNo <> ''
                  AND sa_code IS NOT NULL AND sa_code <> ''
                GROUP BY sa_code, service_area
                ORDER BY service_area
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(fn($row) => [
                'id'           => $this->toUtf8(trim($row['sa_code'] ?? '')),
                'name'         => $this->toUtf8(trim(($row['sa_code'] ?? '') . ' - ' . ($row['service_area'] ?? ''))),
                'member_count' => (int) ($row['member_count'] ?? 0),
                'source'       => 'moresco',
            ], $rows);

        } catch (\Exception $e) {
            Log::error('MorescoDbService::getServiceAreaGroups failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get distinct municipalities with member counts.
     */
    public function getMunicipalityGroups(): array
    {
        try {
            $pdo = $this->getConnection();

            $sql = "
                SELECT
                    Municipality,
                    COUNT(*) AS member_count
                FROM dbo.vw_members_list
                WHERE ContactNo IS NOT NULL AND ContactNo <> ''
                  AND Municipality IS NOT NULL AND Municipality <> ''
                GROUP BY Municipality
                ORDER BY Municipality
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(fn($row) => [
                'id'           => $this->toUtf8(trim($row['Municipality'] ?? '')),
                'name'         => $this->toUtf8(trim($row['Municipality'] ?? '')),
                'member_count' => (int) ($row['member_count'] ?? 0),
                'source'       => 'moresco',
            ], $rows);

        } catch (\Exception $e) {
            Log::error('MorescoDbService::getMunicipalityGroups failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get distinct barangays with member counts.
     */
    public function getBarangayGroups(): array
    {
        try {
            $pdo = $this->getConnection();

            $sql = "
                SELECT
                    Barangay,
                    Municipality,
                    COUNT(*) AS member_count
                FROM dbo.vw_members_list
                WHERE ContactNo IS NOT NULL AND ContactNo <> ''
                  AND Barangay IS NOT NULL AND Barangay <> ''
                GROUP BY Barangay, Municipality
                ORDER BY Municipality, Barangay
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(fn($row) => [
                'id'           => $this->toUtf8(trim($row['Municipality'] ?? '') . '|' . trim($row['Barangay'] ?? '')),
                'name'         => $this->toUtf8(trim($row['Barangay'] ?? '') . ' — ' . trim($row['Municipality'] ?? '')),
                'member_count' => (int) ($row['member_count'] ?? 0),
                'source'       => 'moresco',
            ], $rows);

        } catch (\Exception $e) {
            Log::error('MorescoDbService::getBarangayGroups failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch all members in a given municipality.
     */
    public function getMembersByMunicipality(string $municipality): array
    {
        try {
            $pdo     = $this->getConnection();
            $escaped = str_replace("'", "''", $municipality);

            $sql = "
                SELECT member_ID, MemberName, ContactNo, email,
                       Address, service_area, sa_code, membershipstatus, Municipality, Barangay
                FROM dbo.vw_members_list
                WHERE ContactNo IS NOT NULL AND ContactNo <> ''
                  AND Municipality = '{$escaped}'
                ORDER BY MemberName
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return array_map(fn($row) => $this->mapMember($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));

        } catch (\Exception $e) {
            Log::error('MorescoDbService::getMembersByMunicipality failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch all members in a given barangay within a municipality.
     * $groupId format: "Municipality|Barangay"
     */
    public function getMembersByBarangay(string $groupId): array
    {
        try {
            $parts      = explode('|', $groupId, 2);
            $municipality = str_replace("'", "''", $parts[0] ?? '');
            $barangay     = str_replace("'", "''", $parts[1] ?? '');
            $pdo          = $this->getConnection();

            $sql = "
                SELECT member_ID, MemberName, ContactNo, email,
                       Address, service_area, sa_code, membershipstatus, Municipality, Barangay
                FROM dbo.vw_members_list
                WHERE ContactNo IS NOT NULL AND ContactNo <> ''
                  AND Municipality = '{$municipality}'
                  AND Barangay = '{$barangay}'
                ORDER BY MemberName
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return array_map(fn($row) => $this->mapMember($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));

        } catch (\Exception $e) {
            Log::error('MorescoDbService::getMembersByBarangay failed: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Fetch all members belonging to a given sa_code (service area).
     * Used by MessageController for broadcast to MORESCO service area groups.
     */
    public function getMembersBySaCode(string $saCode): array
    {
        try {
            $pdo = $this->getConnection();

            $sql = "
                SELECT
                    member_ID, MemberName, ContactNo, email,
                    Address, service_area, sa_code, membershipstatus, Municipality, Barangay
                FROM dbo.vw_members_list
                WHERE ContactNo IS NOT NULL AND ContactNo <> ''
                  AND sa_code = ?
                ORDER BY MemberName
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$saCode]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(fn($row) => $this->mapMember($row), $rows);

        } catch (\Exception $e) {
            Log::error('MorescoDbService::getMembersBySaCode failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch the most relevant active outage for a member.
     * Checks for specific account outages first, then falls back to area-wide outages.
     * 
     * @param string $memberId The 5-digit member_ID
     * @param string|null $saCode The service area code (e.g. OSA, JSA)
     * @return array|null 
     */
    public function getMemberOutageData(string $accountNo, ?string $saCode = null): ?array
    {
        try {
            $pdo = $this->getConnection();
            $escaped = trim($accountNo);
            
            // 2. Check for Individual Outage / Workorder
            $sqlIndividual = "
                SELECT TOP 1 *
                FROM dbo.VW_WORKORDERS_LIST 
                WHERE Account_no = ?
                  AND status NOT LIKE '%DONE%'
                  AND status NOT LIKE '%CANCEL%'
                ORDER BY date_created DESC
            ";
            $stmtInd = $pdo->prepare($sqlIndividual);
            $stmtInd->execute([$escaped]);
            $individualRow = $stmtInd->fetch(\PDO::FETCH_ASSOC);

            if ($individualRow) {
                return [
                    'type'               => 'individual',
                    'work_name'          => $this->toUtf8($individualRow['work_name'] ?? 'Unknown Work'),
                    'work_status'        => $this->toUtf8($individualRow['status'] ?? 'PENDING'),
                    'date_created'       => $individualRow['date_created'] ?? 'Unknown Date',
                    'power_interruption' => $this->toUtf8($individualRow['power_interruption'] ?? 'N/A'),
                    'location'           => $this->toUtf8($individualRow['address'] ?? 'Unknown Location'),
                    'remarks'            => $this->toUtf8($individualRow['remarks'] ?? 'None'),
                ];
            }

            // 3. Fallback: Check for Area-Wide Outage using sa_code
            if ($saCode) {
                // Ensure saCode doesn't have extra appended text like "OSA OPOL", just "OSA"
                $saCodeParts = explode(' ', $saCode);
                $cleanSaCode = $saCodeParts[0];

                $sqlGrouped = "
                    SELECT TOP 1 *
                    FROM dbo.VW_WORKORDERS_LIST
                    WHERE account_id IS NULL 
                      AND sa_code = ?
                      AND status NOT LIKE '%DONE%'
                      AND status NOT LIKE '%CANCEL%'
                    ORDER BY date_created DESC
                ";
                $stmtGroup = $pdo->prepare($sqlGrouped);
                $stmtGroup->execute([$cleanSaCode]);
                $groupRow = $stmtGroup->fetch(\PDO::FETCH_ASSOC);

                if ($groupRow) {
                    return [
                        'type'               => 'grouped',
                        'work_name'          => $this->toUtf8($groupRow['work_name'] ?? 'Area Outage'),
                        'work_status'        => $this->toUtf8($groupRow['status'] ?? 'PENDING'),
                        'date_created'       => $groupRow['date_created'] ?? 'Unknown Date',
                        'power_interruption' => $this->toUtf8($groupRow['power_interruption'] ?? 'N/A'),
                        'location'           => $this->toUtf8($groupRow['address'] ?? 'Area-wide'),
                        'remarks'            => $this->toUtf8($groupRow['remarks'] ?? 'None'),
                    ];
                }
            }

            return null; // No active outage found

        } catch (\Exception $e) {
            Log::error('MorescoDbService::getMemberOutageData failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a raw PDO connection to the MORESCO SQL Server via ODBC.
     */
    public function getConnection(): \PDO
    {
        $host     = env('MSDB_HOST', 'localhost');
        $port     = env('MSDB_PORT', '1433');
        $database = env('MSDB_DATABASE', '');
        $username = env('MSDB_USERNAME', '');
        $password = env('MSDB_PASSWORD', '');

        $dsn = "odbc:Driver={SQL Server};Server={$host},{$port};Database={$database};";

        return new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT            => 15,
        ]);
    }

    /**
     * Normalize a raw vw_members_list row into the standard contact shape
     * used by the frontend (same shape as App contacts).
     */
    private function mapMember(array $row): array
    {
        return [
            'id'           => $row['member_ID'] ?? null,
            'name'         => $this->toUtf8($row['MemberName'] ?? ''),
            'phone_number' => $this->toUtf8($row['ContactNo'] ?? ''),
            'email'        => $this->toUtf8($row['email'] ?? ''),
            'address'      => $this->toUtf8($row['Address'] ?? ''),
            'sa_code'      => $this->toUtf8($row['sa_code'] ?? ''),
            'service_area' => $this->toUtf8(trim(($row['sa_code'] ?? '') . ' ' . ($row['service_area'] ?? ''))),
            'status'       => $this->toUtf8($row['membershipstatus'] ?? ''),
            'municipality' => $this->toUtf8($row['Municipality'] ?? ''),
            'barangay'     => $this->toUtf8($row['Barangay'] ?? ''),
            'source'       => 'moresco',
        ];
    }

    /**
     * Safely convert a possibly-Windows-1252 string to clean UTF-8.
     * ODBC returns data in the server's collation encoding, which can be
     * Latin-1 / Windows-1252. json_encode() rejects invalid UTF-8 sequences.
     */
    private function toUtf8(?string $value): string
    {
        if ($value === null) return '';

        $value = trim($value);

        // If it's already valid UTF-8, return as-is
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        // Try converting from Windows-1252 (most common SQL Server collation)
        $converted = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');

        // Final safety net: strip any remaining invalid bytes
        return mb_convert_encoding($converted, 'UTF-8', 'UTF-8');
    }

    /**
     * Get paginated account records joined with their member details
     */
    public function getAccounts(string $search = null, int $limit = 100, int $offset = 0): array
    {
        try {
            $pdo = $this->getConnection();

            $whereClause = "WHERE EXISTS (SELECT 1 FROM dbo.account a WHERE a.member_id = m.member_ID)";
            $params = [];

            if ($search) {
                // Using parameterized queries for LIKE '%search%'
                $escaped = str_replace("'", "''", $search);
                $like    = "'%" . $escaped . "%'";
                $whereClause .= " AND (m.MemberName LIKE {$like} OR m.member_ID IN (SELECT member_id FROM dbo.account WHERE account_no LIKE {$like}))";
            }

            // Grouping by a.member_id is no longer needed since we are querying from vw_members_list
            $sql = "
                SELECT
                    m.member_ID as member_id,
                    m.MemberName,
                    m.membershipstatus,
                    m.service_area
                FROM dbo.vw_members_list m
                {$whereClause}
                ORDER BY m.member_ID
                OFFSET {$offset} ROWS
                FETCH NEXT {$limit} ROWS ONLY
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(function($row) {
                return [
                    'account_no'       => '', // No longer part of the top-level row, frontend uses inspect
                    'member_id'        => $this->toUtf8($row['member_id'] ?? ''),
                    'MemberName'       => $this->toUtf8($row['MemberName'] ?? ''),
                    'membershipstatus' => $this->toUtf8($row['membershipstatus'] ?? ''),
                    'service_area'     => $this->toUtf8($row['service_area'] ?? ''),
                ];
            }, $results);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MorescoDbService::getAccounts failed: ' . $e->getMessage());
            return [];
        }
    }

    public function countAccounts(string $search = null): int
    {
        try {
            $pdo = $this->getConnection();

            $whereClause = "WHERE EXISTS (SELECT 1 FROM dbo.account a WHERE a.member_id = m.member_ID)";
            $params = [];

            if ($search) {
                $escaped = str_replace("'", "''", $search);
                $like    = "'%" . $escaped . "%'";
                $whereClause .= " AND (m.MemberName LIKE {$like} OR m.member_ID IN (SELECT member_id FROM dbo.account WHERE account_no LIKE {$like}))";
            }

            $sql = "
                SELECT COUNT(*) AS total
                FROM dbo.vw_members_list m
                {$whereClause}
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return (int) ($result['total'] ?? 0);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MorescoDbService::countAccounts failed: ' . $e->getMessage());
            return 0;
        }
    }
}
