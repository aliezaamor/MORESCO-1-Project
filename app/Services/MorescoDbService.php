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
                $whereClause = "WHERE (ContactNo is not NULL and ContactNo <> '') and (MemberName LIKE ? OR ContactNo LIKE ? OR email LIKE ?)";
                $likeSearch = '%' . $search . '%';
                $params = [$likeSearch, $likeSearch, $likeSearch];
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
                $whereClause = "WHERE (MemberName LIKE ? OR ContactNo LIKE ? OR email LIKE ?)";
                $likeSearch = '%' . $search . '%';
                $params = [$likeSearch, $likeSearch, $likeSearch];
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
