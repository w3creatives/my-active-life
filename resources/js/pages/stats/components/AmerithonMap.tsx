import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { Map, MapPin, Navigation, Target } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

// Leaflet imports - importing after mounting to avoid SSR issues
let L: any;
let leafletLoaded = false;

interface UserPosition {
  latitude: number;
  longitude: number;
  distance_covered: number;
  user_id?: number;
  user_name?: string;
  team_id?: number;
  team_name?: string;
}

interface RoutePoint {
  lat: number;
  lng: number;
  distance: number;
}

interface AmerithonMapProps {
  className?: string;
  dataFor?: string;
}

export default function AmerithonMap({ className = '', dataFor = 'you' }: AmerithonMapProps) {
  const { auth } = usePage<SharedData>().props;
  const mapRef = useRef<HTMLDivElement>(null);
  const leafletMapRef = useRef<any>(null);
  const [loading, setLoading] = useState(true);
  const [mapData, setMapData] = useState<{
    user_position?: UserPosition;
    team_position?: UserPosition;
    total_distance?: number;
    completion_percentage?: number;
    route_line?: RoutePoint[];
    message?: string;
  }>({});
  const [isClient, setIsClient] = useState(false);

  // Import Leaflet only on client side
  useEffect(() => {
    setIsClient(true);
    if (!leafletLoaded) {
      import('leaflet').then((leafletModule) => {
        L = leafletModule.default;
        leafletLoaded = true;

        // Import Leaflet CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        // Fix default markers
        delete (L.Icon.Default.prototype as any)._getIconUrl;
        L.Icon.Default.mergeOptions({
          iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
          iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
          shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
        });
      });
    }
  }, []);

  useEffect(() => {
    const fetchMapData = async () => {
      setLoading(true); // Set loading to true when dataFor changes
      try {
        const routeName = dataFor === 'team' ? 'teamstats' : 'userstats';
        const response = await axios.get(route(routeName, ['amerithon-map']), {
          params: {
            event_id: auth.preferred_event.id,
            user_id: auth.user.id,
          },
        });
        setMapData(response.data);
        setLoading(false);
      } catch (err) {
        console.error('Error fetching Amerithon map data:', err);
        setLoading(false);
      }
    };

    fetchMapData();
  }, [dataFor]);

  useEffect(() => {
    if (!isClient || !leafletLoaded || !L || loading || !mapRef.current) {
      return;
    }

    // Initialize map with USA bounds (based on Ruby implementation)
    const usaBounds = [
      [20.2274717533, -129.850846949], // Southwest
      [49.3031683564, -70.8199872097], // Northeast
    ];

    const map = L.map(mapRef.current, {
      zoomControl: true,
      maxZoom: 13,
      minZoom: 4,
      scrollWheelZoom: true,
      doubleClickZoom: true,
      touchZoom: true,
    }).fitBounds(usaBounds);

    leafletMapRef.current = map;

    // Add OpenStreetMap tile layer as fallback
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 18,
    }).addTo(map);

    // Add route line if available
    if (mapData.route_line && mapData.route_line.length > 0) {
      const routeCoordinates = mapData.route_line
        .filter(point => point.lat && point.lng && point.lat !== 0 && point.lng !== 0)
        .map((point) => [point.lat, point.lng]);

      if (routeCoordinates.length > 1) {
        // Add the complete route line with better styling
        const routeLine = L.polyline(routeCoordinates, {
          color: '#4A90E2',
          weight: 4,
          opacity: 0.8,
          smoothFactor: 1.0,
        }).addTo(map);

        // Add progress line (completed portion)
        const currentPosition = dataFor === 'team' ? mapData.team_position : mapData.user_position;
        if (currentPosition && mapData.completion_percentage > 0) {
          const completedPointsCount = Math.floor((mapData.completion_percentage / 100) * routeCoordinates.length);
          
          if (completedPointsCount > 0) {
            const completedCoordinates = routeCoordinates.slice(0, completedPointsCount + 1);

            if (completedCoordinates.length > 1) {
              L.polyline(completedCoordinates, {
                color: '#28a745',
                weight: 6,
                opacity: 1.0,
              }).addTo(map);
            }
          }
        }

        // Fit map to show the route with some padding
        try {
          map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
        } catch (e) {
          console.warn('Could not fit bounds to route');
        }
      }
    }

    // Add user/team position marker with custom styling
    const currentPosition = dataFor === 'team' ? mapData.team_position : mapData.user_position;
    if (currentPosition) {
      // Create a custom icon for the user marker
      const userIcon = L.divIcon({
        className: 'custom-user-marker',
        html: `
          <div style="
            background-color: #ff4444;
            border: 3px solid white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
          "></div>
        `,
        iconSize: [20, 20],
        iconAnchor: [10, 10],
      });

      const userMarker = L.marker([currentPosition.latitude, currentPosition.longitude], { icon: userIcon }).addTo(map);

      const positionName = dataFor === 'team' 
        ? currentPosition.team_name || 'Team Position'
        : currentPosition.user_name || 'Your Position';

      userMarker.bindPopup(`
        <div class="text-center p-3">
          <h3 class="font-semibold text-lg mb-2">${positionName}</h3>
          <p class="text-sm text-gray-600 mb-1">${formatDistance(currentPosition.distance_covered)} miles covered</p>
          <p class="text-xs text-gray-500">${mapData.completion_percentage?.toFixed(1)}% complete</p>
        </div>
      `);

      // Only center on position if no route line is available
      if (!mapData.route_line || mapData.route_line.length === 0) {
        map.setView([currentPosition.latitude, currentPosition.longitude], 6);
      }
    } else if (!mapData.route_line || mapData.route_line.length === 0) {
      // If no user position and no route, show the full USA view
      map.fitBounds(usaBounds);
    }

    // Cleanup function
    return () => {
      if (leafletMapRef.current) {
        leafletMapRef.current.remove();
        leafletMapRef.current = null;
      }
    };
  }, [isClient, leafletLoaded, loading, mapData]);

  const formatDistance = (distance: number) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 1,
    }).format(distance);
  };

  const progressStats = useMemo(() => {
    const currentPosition = dataFor === 'team' ? mapData.team_position : mapData.user_position;
    if (!currentPosition || !mapData.total_distance) {
      return {
        distanceCovered: 0,
        totalDistance: 0,
        completionPercentage: 0,
        remainingDistance: 0,
      };
    }

    const distanceCovered = currentPosition.distance_covered;
    const totalDistance = mapData.total_distance;
    const completionPercentage = (distanceCovered / totalDistance) * 100;
    const remainingDistance = Math.max(0, totalDistance - distanceCovered);

    return {
      distanceCovered,
      totalDistance,
      completionPercentage,
      remainingDistance,
    };
  }, [mapData]);

  if (loading) {
    return (
      <Card className={className}>
        <CardHeader>
          <Skeleton className="mb-2 h-6 w-48" />
          <Skeleton className="h-4 w-full" />
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="grid grid-cols-3 gap-4">
              <Skeleton className="h-20 w-full rounded-lg" />
              <Skeleton className="h-20 w-full rounded-lg" />
              <Skeleton className="h-20 w-full rounded-lg" />
            </div>
            <Skeleton className="h-96 w-full rounded-lg" />
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={className}>
      <CardHeader className="pb-4">
        <div className="flex items-center gap-2">
          <Map className="text-primary h-6 w-6" />
          <CardTitle className="text-xl">Amerithon Journey Map</CardTitle>
        </div>
        <CardDescription>Track your progress across America in the Amerithon challenge</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Progress Statistics */}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div className="bg-primary/5 rounded-lg border p-4 text-center">
            <div className="mb-2 flex items-center justify-center gap-2">
              <Navigation className="text-primary h-4 w-4" />
              <span className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Distance Covered</span>
            </div>
            <div className="text-primary text-2xl font-bold">{formatDistance(progressStats.distanceCovered)}</div>
            <div className="text-muted-foreground text-sm">miles</div>
          </div>

          <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-center dark:border-green-800 dark:bg-green-950/20">
            <div className="mb-2 flex items-center justify-center gap-2">
              <Target className="h-4 w-4 text-green-600 dark:text-green-400" />
              <span className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Completion</span>
            </div>
            <div className="text-2xl font-bold text-green-600 dark:text-green-400">{progressStats.completionPercentage.toFixed(1)}%</div>
            <div className="text-muted-foreground text-sm">complete</div>
          </div>

          <div className="bg-muted/30 rounded-lg border p-4 text-center">
            <div className="mb-2 flex items-center justify-center gap-2">
              <MapPin className="text-muted-foreground h-4 w-4" />
              <span className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Remaining</span>
            </div>
            <div className="text-2xl font-bold">{formatDistance(progressStats.remainingDistance)}</div>
            <div className="text-muted-foreground text-sm">miles to go</div>
          </div>
        </div>

        {/* Progress Bar */}
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-sm font-medium">Journey Progress</span>
            <Badge variant="outline">
              {formatDistance(progressStats.distanceCovered)} / {formatDistance(progressStats.totalDistance)} miles
            </Badge>
          </div>
          <div className="bg-muted/30 h-3 w-full rounded-full">
            <div
              className="h-3 rounded-full bg-gradient-to-r from-blue-500 to-green-500 transition-all duration-500"
              style={{ width: `${Math.min(progressStats.completionPercentage, 100)}%` }}
            />
          </div>
        </div>

        {/* Map Container */}
        <div className="relative">
          <div ref={mapRef} className="h-96 w-full rounded-lg border shadow-sm" style={{ minHeight: '384px' }} />

          {!(dataFor === 'team' ? mapData.team_position : mapData.user_position) && !loading && (
            <div className="bg-muted/80 absolute inset-0 flex items-center justify-center rounded-lg">
              <div className="text-center">
                <Map className="text-muted-foreground/50 mx-auto mb-4 h-12 w-12" />
                <p className="text-muted-foreground mb-2">No position data available</p>
                <p className="text-muted-foreground/70 text-sm">Start logging activities to see your position on the map</p>
              </div>
            </div>
          )}
        </div>

        {/* Map Legend */}
        <div className="text-muted-foreground flex items-center justify-center gap-4 text-sm">
          {mapData.route_line && mapData.route_line.length > 0 && (
            <div className="flex items-center gap-2">
              <div className="h-0.5 w-4 bg-blue-500 shadow-sm" />
              <span>Amerithon Route</span>
            </div>
          )}
          {(dataFor === 'team' ? mapData.team_position : mapData.user_position) && mapData.completion_percentage > 0 && (
            <div className="flex items-center gap-2">
              <div className="h-0.5 w-4 bg-green-500 shadow-sm" />
              <span>{dataFor === 'team' ? 'Team Progress' : 'Your Progress'}</span>
            </div>
          )}
          {(dataFor === 'team' ? mapData.team_position : mapData.user_position) && (
            <div className="flex items-center gap-2">
              <div className="h-3 w-3 rounded-full border border-white bg-red-500 shadow-sm" />
              <span>{dataFor === 'team' ? 'Team Position' : 'Your Position'}</span>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
