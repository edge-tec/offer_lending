<?php
declare(strict_types=1);

namespace App;

/**
 * Analytics & Performance Metrics Engine
 */
class Analytics
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get High-Level Summary Metrics
     */
    public function getSummary(string $period = 'all'): array
    {
        $dateCondition = $this->buildDateCondition('created_at', $period);

        // 1. Total Pageviews & Visitors
        $pvSql = "SELECT COUNT(*) as total_pvs, COUNT(DISTINCT ip_hash) as unique_visitors FROM pageviews WHERE {$dateCondition}";
        $pvRow = $this->db->query($pvSql)->fetch();
        $totalPageviews = (int)($pvRow['total_pvs'] ?? 0);
        $uniqueVisitors = (int)($pvRow['unique_visitors'] ?? 0);

        // 2. Total & Unique Clicks
        $clickSql = "SELECT COUNT(*) as total_clicks, COUNT(DISTINCT ip_hash) as unique_clicks FROM clicks WHERE {$dateCondition}";
        $clickRow = $this->db->query($clickSql)->fetch();
        $totalClicks = (int)($clickRow['total_clicks'] ?? 0);
        $uniqueClicks = (int)($clickRow['unique_clicks'] ?? 0);

        // 3. CTR
        $ctr = $totalPageviews > 0 ? round(($totalClicks / $totalPageviews) * 100, 2) : ($totalClicks > 0 ? 100.0 : 0.0);

        // 4. Quick presets: Today, Last 7 Days, Last 30 Days clicks
        $todayClicks = (int)$this->db->query("SELECT COUNT(*) FROM clicks WHERE created_at >= CURRENT_DATE")->fetchColumn();
        
        $driver = Database::getInstance()->getDriver();
        if ($driver === 'sqlite') {
            $weekClicks = (int)$this->db->query("SELECT COUNT(*) FROM clicks WHERE created_at >= datetime('now', '-7 days')")->fetchColumn();
            $monthClicks = (int)$this->db->query("SELECT COUNT(*) FROM clicks WHERE created_at >= datetime('now', '-30 days')")->fetchColumn();
        } else {
            $weekClicks = (int)$this->db->query("SELECT COUNT(*) FROM clicks WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
            $monthClicks = (int)$this->db->query("SELECT COUNT(*) FROM clicks WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
        }

        return [
            'total_pageviews' => $totalPageviews,
            'unique_visitors' => $uniqueVisitors,
            'total_clicks' => $totalClicks,
            'unique_clicks' => $uniqueClicks,
            'ctr' => $ctr,
            'today_clicks' => $todayClicks,
            'week_clicks' => $weekClicks,
            'month_clicks' => $monthClicks,
        ];
    }

    /**
     * Get Clicks Time-Series for Chart.js
     */
    public function getClickTrend(int $days = 14): array
    {
        $driver = Database::getInstance()->getDriver();
        $labels = [];
        $clickData = [];
        $visitorData = [];

        // Build array of past $days
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $displayDate = date('M d', strtotime("-{$i} days"));
            $labels[$date] = $displayDate;
            $clickData[$date] = 0;
            $visitorData[$date] = 0;
        }

        $minDate = date('Y-m-d 00:00:00', strtotime("-" . ($days - 1) . " days"));

        // Fetch Clicks grouped by date
        if ($driver === 'sqlite') {
            $stmt = $this->db->prepare("
                SELECT strftime('%Y-%m-%d', created_at) as cdate, COUNT(*) as cnt 
                FROM clicks 
                WHERE created_at >= ? 
                GROUP BY cdate
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as cdate, COUNT(*) as cnt 
                FROM clicks 
                WHERE created_at >= ? 
                GROUP BY cdate
            ");
        }
        $stmt->execute([$minDate]);
        while ($row = $stmt->fetch()) {
            if (isset($clickData[$row['cdate']])) {
                $clickData[$row['cdate']] = (int)$row['cnt'];
            }
        }

        // Fetch Pageviews grouped by date
        if ($driver === 'sqlite') {
            $stmt = $this->db->prepare("
                SELECT strftime('%Y-%m-%d', created_at) as pdate, COUNT(*) as cnt 
                FROM pageviews 
                WHERE created_at >= ? 
                GROUP BY pdate
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as pdate, COUNT(*) as cnt 
                FROM pageviews 
                WHERE created_at >= ? 
                GROUP BY pdate
            ");
        }
        $stmt->execute([$minDate]);
        while ($row = $stmt->fetch()) {
            if (isset($visitorData[$row['pdate']])) {
                $visitorData[$row['pdate']] = (int)$row['cnt'];
            }
        }

        return [
            'labels' => array_values($labels),
            'clicks' => array_values($clickData),
            'pageviews' => array_values($visitorData)
        ];
    }

    /**
     * Get Device Breakdown for Doughnut Chart
     */
    public function getDeviceBreakdown(string $period = 'all'): array
    {
        $dateCondition = $this->buildDateCondition('created_at', $period);
        $sql = "
            SELECT device_type, COUNT(*) as count 
            FROM clicks 
            WHERE {$dateCondition} 
            GROUP BY device_type 
            ORDER BY count DESC
        ";
        $rows = $this->db->query($sql)->fetchAll();
        $counts = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0, 'other' => 0];
        foreach ($rows as $r) {
            $dev = $r['device_type'] ?? 'other';
            if (isset($counts[$dev])) {
                $counts[$dev] += (int)$r['count'];
            } else {
                $counts['other'] += (int)$r['count'];
            }
        }
        return $counts;
    }

    /**
     * Get Country Breakdown
     */
    public function getCountryBreakdown(int $limit = 8, string $period = 'all'): array
    {
        $dateCondition = $this->buildDateCondition('created_at', $period);
        $sql = "
            SELECT country, COUNT(*) as count 
            FROM clicks 
            WHERE {$dateCondition} 
            GROUP BY country 
            ORDER BY count DESC 
            LIMIT {$limit}
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get Top Traffic Sources
     */
    public function getTrafficSources(int $limit = 6, string $period = 'all'): array
    {
        $dateCondition = $this->buildDateCondition('created_at', $period);
        $sql = "
            SELECT 
                COALESCE(NULLIF(utm_source, ''), NULLIF(referer, ''), 'Direct / Organic') as source_name,
                COUNT(*) as count
            FROM clicks
            WHERE {$dateCondition}
            GROUP BY source_name
            ORDER BY count DESC
            LIMIT {$limit}
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Detailed Campaign Performance Table
     */
    public function getCampaignPerformance(string $period = 'all'): array
    {
        $dateCondition = $this->buildDateCondition('cl.created_at', $period);
        $sql = "
            SELECT 
                c.id,
                c.campaign_code,
                c.name,
                c.target_url,
                c.is_active,
                c.is_default,
                COUNT(cl.id) as total_clicks,
                COUNT(DISTINCT cl.ip_hash) as unique_clicks,
                MAX(cl.created_at) as last_clicked_at
            FROM campaigns c
            LEFT JOIN clicks cl ON cl.campaign_id = c.id AND {$dateCondition}
            GROUP BY c.id
            ORDER BY total_clicks DESC, c.is_default DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get Raw Recent Clicks Log
     */
    public function getRecentClicks(int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT cl.*, c.name as campaign_name, c.campaign_code
            FROM clicks cl
            LEFT JOIN campaigns c ON c.id = cl.campaign_id
            ORDER BY cl.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Build SQL date condition string safely
     */
    private function buildDateCondition(string $column, string $period): string
    {
        $driver = Database::getInstance()->getDriver();
        return match ($period) {
            'today' => $driver === 'sqlite' ? "{$column} >= date('now')" : "{$column} >= CURRENT_DATE",
            'week' => $driver === 'sqlite' ? "{$column} >= datetime('now', '-7 days')" : "{$column} >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => $driver === 'sqlite' ? "{$column} >= datetime('now', '-30 days')" : "{$column} >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            default => "1=1"
        };
    }
}

