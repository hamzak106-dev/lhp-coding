/** Lean event shape returned by the grid + map feeds (EventTransformer::forList). */
export interface EventListItem {
    id: string;
    title: string;
    category: string;
    status: string;
    start_iso: string | null;
    timezone: string;
    location_label: string;
    city: string;
    lat: number | null;
    lng: number | null;
    price: number;
    currency: string;
    venue: string | null;
    image_url: string | null;
}

/** Filter metadata shared with both visual pages. */
export interface CityOption {
    value: string;
    label: string;
    lat: number;
    lng: number;
}
