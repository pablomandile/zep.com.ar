export type ShortLink = {
    id: number;
    user_id: number;
    code: string;
    short_url: string;
    original_url: string;
    title: string | null;
    is_custom: boolean;
    is_active: boolean;
    expires_at: string | null;
    clicks_count: number;
    last_clicked_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type StatRow = { label: string; clicks: number };

export type LinkStats = {
    from: string;
    to: string;
    total_clicks: number;
    period_clicks: number;
    unique_visitors: number;
    last_clicked_at: string | null;
    per_day: { date: string; clicks: number }[];
    referers: StatRow[];
    devices: StatRow[];
    browsers: StatRow[];
    os: StatRow[];
    countries: StatRow[];
};
