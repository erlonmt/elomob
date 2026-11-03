export type Mode = { id: number; code: string; name: string; color: string };
export type Stop = {
  id: number;
  name: string;
  address: string;
  latitude: number;
  longitude: number;
  transport_mode_id: number;
  mode?: string;
  mode_name?: string;
  color?: string;
  accessible: boolean;
  tactile_paving: boolean;
  visual_signs: boolean;
  bikes_available: number | null;
  docks_available: number | null;
  distance_meters?: number;
};
export type Line = {
  id: number;
  code: string;
  name: string;
  mode: string;
  starts_at: string;
  ends_at: string;
  frequency_minutes: number;
  fare_cents: number;
  /** The two ways of the line, each with its stops in order and the minutes from the first one. */
  directions: { to: string; stops: { id: number; name: string; minutes: number }[] }[];
};
export type Integration = {
  id: number;
  stop_id: number;
  name: string;
  from_mode: string;
  to_mode: string;
  from_name: string;
  to_name: string;
  transfer_minutes: number;
  description: string;
  fare_integrated: boolean;
};
export type Catalog = { stops: Stop[]; modes: Mode[]; lines: Line[]; integrations: Integration[] };
export type User = {
  id: number;
  name: string;
  email: string;
  preferred_route: string;
  transport_modes: string[];
  accessibility_needs: string[];
};
export type Session = { user: User | null; csrf_token: string };
export type Step = {
  id?: number;
  connection_id?: number;
  origin: string;
  destination: string;
  mode: string;
  mode_name: string;
  color?: string;
  line_code: string | null;
  minutes: number;
  cost_cents: number;
  fare_integrated?: boolean;
  distance_meters: number;
  transfer_minutes?: number;
  wait_minutes?: number;
  has_stairs?: boolean;
  accessible_for?: string[];
  origin_latitude?: number;
  origin_longitude?: number;
  destination_latitude?: number;
  destination_longitude?: number;
};
export type Position = [number, number];
export type GeoJSON = {
  type: 'FeatureCollection';
  features: {
    type: 'Feature';
    geometry: { type: 'LineString'; coordinates: Position[] };
    properties: {
      mode?: string;
      color?: string;
      origin?: string;
      destination?: string;
      [key: string]: unknown;
    };
  }[];
};
export type Itinerary = {
  id: number;
  trip_search_id?: number;
  criterion: string;
  total_minutes: number;
  total_cost_cents: number;
  total_distance_meters: number;
  transfers: number;
  steps: Step[];
  geojson?: GeoJSON;
  search?: SearchSummary;
};
export type Search = {
  id: number;
  origin: string;
  destination: string;
  origin_id: number;
  destination_id: number;
  filters: { modes?: string[]; accessibility_needs?: string[] };
  itineraries: Itinerary[];
  created_at?: string;
  departure_at?: string | null;
};
/** The search an itinerary belongs to, as the itinerary detail endpoint describes it. */
export type SearchSummary = Pick<
  Search,
  | 'id'
  | 'origin'
  | 'destination'
  | 'origin_id'
  | 'destination_id'
  | 'filters'
  | 'departure_at'
  | 'created_at'
>;
export type Favorite = {
  id: number;
  label: string;
  stop_id: number;
  name: string;
  address: string;
  stop?: Stop;
};
export type Envelope<T> = { data: T; source?: string; meta?: { source?: string } };
