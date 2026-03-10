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
    public function getMemberByAccountNumber(string $accountNumber): ?array
    {
        try {
            $pdo = $this->getConnection();

            $sql = "
                SELECT
                    member_ID, MemberName, ContactNo, email,
                    Address, service_area, sa_code, membershipstatus, Municipality, Barangay
                FROM dbo.vw_members_list
                WHERE member_ID = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([trim($accountNumber)]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $row ? $this->mapMember($row) : null;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MorescoDbService::getMemberByAccountNumber failed: ' . $e->getMessage());
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
    public function getMemberBillingData(string $accountNumber): array
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
            $escaped = trim($accountNumber);

            // ── 0. Fast Lookup: Map member_ID to padded account_no ────────────
            // The billing views store padded account numbers (e.g. 08050560).
            // This query fetches all exact padded account numbers tied to the member.
            $stmtMap = $pdo->prepare("
                SELECT account_no
                FROM dbo.account
                WHERE member_id = ?
            ");
            $stmtMap->execute([$escaped]);
            $mappedRows = $stmtMap->fetchAll(\PDO::FETCH_ASSOC);
            $mappedAccounts = array_map(fn($r) => $r['account_no'], $mappedRows);

            // If the member doesn't have an entry in dbo.account, fallback to exact string
            if (empty($mappedAccounts)) {
                $mappedAccounts = [$escaped];
            }

            // Create placeholders for the IN clause (e.g., ?, ?, ?)
            $inPlaceholders = str_repeat('?,', count($mappedAccounts) - 1) . '?';

            // ── Latest account info (due date, status) ──────────────────────
            $stmtAcc = $pdo->prepare("
                SELECT TOP 1
                    due_date,
                    status_id
                FROM dbo.VW_ACCOUNTS_METER_READING
                WHERE account_no IN ($inPlaceholders)
                ORDER BY billmo DESC, rdng_date DESC
            ");
            $stmtAcc->execute($mappedAccounts);
            $acc = $stmtAcc->fetch(\PDO::FETCH_ASSOC);

            // ── Latest bill (debit row) ───────────────────────────────────────
            $stmtBill = $pdo->prepare("
                SELECT TOP 1
                    debit       AS bill_amount,
                    balance     AS balance,
                    trans_date  AS bill_date
                FROM dbo.vw_AccountTransactions
                WHERE account_no IN ($inPlaceholders)
                  AND debit > 0
                  AND (isReversed IS NULL OR isReversed = 0)
                ORDER BY trans_date DESC
            ");
            $stmtBill->execute($mappedAccounts);
            $bill = $stmtBill->fetch(\PDO::FETCH_ASSOC);

            // ── Latest payment (credit row) ───────────────────────────────────
            $stmtPay = $pdo->prepare("
                SELECT TOP 1
                    credit              AS payment_amount,
                    trans_date          AS payment_date,
                    [official_receipt]  AS or_number
                FROM dbo.vw_AccountTransactions
                WHERE account_no IN ($inPlaceholders)
                  AND credit > 0
                  AND (isReversed IS NULL OR isReversed = 0)
                ORDER BY trans_date DESC
            ");
            $stmtPay->execute($mappedAccounts);
            $pay = $stmtPay->fetch(\PDO::FETCH_ASSOC);

            if (!$bill && !$pay) {
                return $empty;
            }

            // ── Format bill fields ────────────────────────────────────────────
            $billAmount     = 'N/A';
            $billingPeriod  = 'N/A';
            $dueDate        = 'N/A';
            $balance        = 'N/A';

            if ($bill) {
                $billAmount    = '₱' . number_format((float)($bill['bill_amount'] ?? 0), 2);
                $balance       = '₱' . number_format((float)($bill['balance']    ?? 0), 2);
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
    public function getMemberOutageData(string $memberId, ?string $saCode = null): ?array
    {
        try {
            $pdo = $this->getConnection();
            
            // 1. Map member_ID to padded account_no(s)
            $stmtMap = $pdo->prepare("SELECT account_no FROM dbo.account WHERE member_id = ?");
            $stmtMap->execute([$memberId]);
            $rows = $stmtMap->fetchAll(\PDO::FETCH_ASSOC);
            $mapped = array_map(fn($r) => $r['account_no'], $rows);
            if (empty($mapped)) $mapped = [$memberId];

            $inPlaceholders = implode(',', array_fill(0, count($mapped), '?'));
            
            // 2. Check for Individual Outage / Workorder
            $sqlIndividual = "
                SELECT TOP 1 *
                FROM dbo.VW_WORKORDERS_LIST 
                WHERE Account_no IN ($inPlaceholders)
                  AND status NOT LIKE '%DONE%'
                  AND status NOT LIKE '%CANCEL%'
                ORDER BY date_created DESC
            ";
            $stmtInd = $pdo->prepare($sqlIndividual);
            $stmtInd->execute($mapped);
            $individualRow = $stmtInd->fetch(\PDO::FETCH_ASSOC);

            if ($individualRow) {
                return [
                    'type'               => 'individual',
                    'work_name'          => $this->toUtf8($individualRow['work_name'] ?? 'Unknown Work'),
                    'work_status'        => $this->toUtf8($individualRow['status'] ?? 'PENDING'),
                    'date_created'       => $individualRow['date_created'] ?? 'Unknown Date',
                    'power_interruption' => $this->toUtf8($individualRow['power_interruption'] ?? 'N/A'),
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
}
