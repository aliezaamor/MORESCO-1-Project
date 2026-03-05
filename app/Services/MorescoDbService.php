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
     * Returns an array with keys matching the reply placeholders:
     *   bill_amount, billing_period, due_date,
     *   last_payment_amount, last_payment_date, or_number
     *
     * TODO: Replace the query below with the correct billing view/table name
     *       once the MORESCO billing schema is confirmed.
     *       Example views to check: vw_billing, vw_member_bills, vw_payments
     */
    public function getMemberBillingData(string $accountNumber): array
    {
        $empty = [
            'bill_amount'          => 'N/A',
            'billing_period'       => 'N/A',
            'due_date'             => 'N/A',
            'last_payment_amount'  => 'N/A',
            'last_payment_date'    => 'N/A',
            'or_number'            => 'N/A',
        ];

        try {
            $pdo = $this->getConnection();

            // TODO: Replace 'dbo.vw_billing'/'dbo.vw_payments' with the real view/table names.
            // The column names below are also guesses — adjust once confirmed.
            /*
            $stmt = $pdo->prepare("
                SELECT TOP 1
                    b.BillAmount         AS bill_amount,
                    b.BillingPeriod      AS billing_period,
                    b.DueDate            AS due_date,
                    p.PaymentAmount      AS last_payment_amount,
                    p.PaymentDate        AS last_payment_date,
                    p.ORNumber           AS or_number
                FROM dbo.vw_billing b
                LEFT JOIN dbo.vw_payments p ON p.member_ID = b.member_ID
                WHERE b.member_ID = ?
                ORDER BY b.DueDate DESC
            ");
            $stmt->execute([trim($accountNumber)]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                return [
                    'bill_amount'         => '₱' . number_format((float)$row['bill_amount'], 2),
                    'billing_period'      => $row['billing_period'] ?? 'N/A',
                    'due_date'            => $row['due_date']        ?? 'N/A',
                    'last_payment_amount' => '₱' . number_format((float)$row['last_payment_amount'], 2),
                    'last_payment_date'   => $row['last_payment_date'] ?? 'N/A',
                    'or_number'           => $row['or_number']          ?? 'N/A',
                ];
            }
            */

            // ← Remove the comment block above and uncomment when the real table/view is confirmed.
            return $empty;

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
     * Get a raw PDO connection to the MORESCO SQL Server via ODBC.
     */
    private function getConnection(): \PDO
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
