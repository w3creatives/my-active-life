import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Map, MapPin, Navigation, Target } from 'lucide-react';
import { useEffect, useRef, useState, useMemo } from 'react';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

// Leaflet imports - importing after mounting to avoid SSR issues
let L: any;
let leafletLoaded = false;

interface UserPosition {
  latitude: number;
  longitude: number;
  distance_covered: number;
  user_id: number;
  user_name?: string;
}

interface RoutePoint {
  lat: number;
  lng: number;
  distance: number;
}

interface AmerithonMapProps {
  className?: string;
}

export default function AmerithonMap({ className = '' }: AmerithonMapProps) {
  const { auth } = usePage<SharedData>().props;
  const mapRef = useRef<HTMLDivElement>(null);
  const leafletMapRef = useRef<any>(null);
  const [loading, setLoading] = useState(true);
  const [mapData, setMapData] = useState<{
    user_position?: UserPosition;
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
      try {
        const response = await axios.get(route('userstats', ['amerithon-map']), {
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
  }, []);

  useEffect(() => {
    if (!isClient || !leafletLoaded || !L || loading || !mapRef.current) {
      return;
    }

    // Initialize map with USA bounds (based on Ruby implementation)
    const usaBounds = [
      [20.2274717533, -129.850846949], // Southwest
      [49.3031683564, -70.8199872097]   // Northeast
    ];

    const map = L.map(mapRef.current, {
      zoomControl: true,
      maxZoom: 13,
      minZoom: 4,
      scrollWheelZoom: true,
      doubleClickZoom: true,
      touchZoom: true
    }).fitBounds(usaBounds);

    leafletMapRef.current = map;

    // Add OpenStreetMap tile layer as fallback
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 18,
    }).addTo(map);

    // Add route line if available
    if (mapData.route_line && mapData.route_line.length > 0) {
      const routeCoordinates = mapData.route_line.map(point => [point.lat, point.lng]);
      
      // Add the complete route line
      L.polyline(routeCoordinates, {
        color: '#4A90E2',
        weight: 4,
        opacity: 0.7,
        smoothFactor: 1
      }).addTo(map);

      // Add progress line (completed portion)
      if (mapData.user_position && mapData.completion_percentage > 0) {
        const completedPointsCount = Math.floor((mapData.completion_percentage / 100) * mapData.route_line.length);
        const completedCoordinates = routeCoordinates.slice(0, completedPointsCount + 1);
        
        if (completedCoordinates.length > 1) {
          L.polyline(completedCoordinates, {
            color: '#28a745',
            weight: 6,
            opacity: 0.9
          }).addTo(map);
        }
      }
    }

    // Add user position marker with custom styling
    if (mapData.user_position) {
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
        iconAnchor: [10, 10]
      });

      const userMarker = L.marker([
        mapData.user_position.latitude,
        mapData.user_position.longitude
      ], { icon: userIcon }).addTo(map);

      userMarker.bindPopup(`
        <div class="text-center p-3">
          <h3 class="font-semibold text-lg mb-2">${mapData.user_position.user_name || 'Your Position'}</h3>
          <p class="text-sm text-gray-600 mb-1">${formatDistance(mapData.user_position.distance_covered)} miles covered</p>
          <p class="text-xs text-gray-500">${mapData.completion_percentage?.toFixed(1)}% complete</p>
        </div>
      `);

      // Center map on user position with appropriate zoom
      map.setView([mapData.user_position.latitude, mapData.user_position.longitude], 6);
    } else {
      // If no user position, show the full USA view
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
    if (!mapData.user_position || !mapData.total_distance) {
      return {
        distanceCovered: 0,
        totalDistance: 0,
        completionPercentage: 0,
        remainingDistance: 0
      };
    }

    const distanceCovered = mapData.user_position.distance_covered;
    const totalDistance = mapData.total_distance;
    const completionPercentage = (distanceCovered / totalDistance) * 100;
    const remainingDistance = Math.max(0, totalDistance - distanceCovered);

    return {
      distanceCovered,
      totalDistance,
      completionPercentage,
      remainingDistance
    };
  }, [mapData]);

  if (loading) {
    return (
      <Card className={className}>
        <CardHeader>
          <Skeleton className="h-6 w-48 mb-2" />
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
          <Map className="h-6 w-6 text-primary" />
          <CardTitle className="text-xl">Amerithon Journey Map</CardTitle>
        </div>
        <CardDescription>
          Track your progress across America in the Amerithon challenge
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Progress Statistics */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="text-center p-4 rounded-lg bg-primary/5 border">
            <div className="flex items-center justify-center gap-2 mb-2">
              <Navigation className="h-4 w-4 text-primary" />
              <span className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Distance Covered
              </span>
            </div>
            <div className="text-2xl font-bold text-primary">
              {formatDistance(progressStats.distanceCovered)}
            </div>
            <div className="text-sm text-muted-foreground">miles</div>
          </div>

          <div className="text-center p-4 rounded-lg bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800">
            <div className="flex items-center justify-center gap-2 mb-2">
              <Target className="h-4 w-4 text-green-600 dark:text-green-400" />
              <span className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Completion
              </span>
            </div>
            <div className="text-2xl font-bold text-green-600 dark:text-green-400">
              {progressStats.completionPercentage.toFixed(1)}%
            </div>
            <div className="text-sm text-muted-foreground">complete</div>
          </div>

          <div className="text-center p-4 rounded-lg bg-muted/30 border">
            <div className="flex items-center justify-center gap-2 mb-2">
              <MapPin className="h-4 w-4 text-muted-foreground" />
              <span className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Remaining
              </span>
            </div>
            <div className="text-2xl font-bold">
              {formatDistance(progressStats.remainingDistance)}
            </div>
            <div className="text-sm text-muted-foreground">miles to go</div>
          </div>
        </div>

        {/* Progress Bar */}
        <div className="space-y-2">
          <div className="flex justify-between items-center">
            <span className="text-sm font-medium">Journey Progress</span>
            <Badge variant="outline">
              {formatDistance(progressStats.distanceCovered)} / {formatDistance(progressStats.totalDistance)} miles
            </Badge>
          </div>
          <div className="w-full bg-muted/30 rounded-full h-3">
            <div
              className="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-500"
              style={{ width: `${Math.min(progressStats.completionPercentage, 100)}%` }}
            />
          </div>
        </div>

        {/* Map Container */}
        <div className="relative">
          <div
            ref={mapRef}
            className="h-96 w-full rounded-lg border shadow-sm"
            style={{ minHeight: '384px' }}
          />

          {!mapData.user_position && !loading && (
            <div className="absolute inset-0 flex items-center justify-center bg-muted/80 rounded-lg">
              <div className="text-center">
                <Map className="mx-auto h-12 w-12 text-muted-foreground/50 mb-4" />
                <p className="text-muted-foreground mb-2">No position data available</p>
                <p className="text-sm text-muted-foreground/70">
                  Start logging activities to see your position on the map
                </p>
              </div>
            </div>
          )}
        </div>

        {/* Map Legend */}
        <div className="flex items-center justify-center gap-4 text-sm text-muted-foreground">
          {mapData.route_line && mapData.route_line.length > 0 && (
            <div className="flex items-center gap-2">
              <div className="w-4 h-0.5 bg-blue-500 shadow-sm" />
              <span>Amerithon Route</span>
            </div>
          )}
          {mapData.user_position && mapData.completion_percentage > 0 && (
            <div className="flex items-center gap-2">
              <div className="w-4 h-0.5 bg-green-500 shadow-sm" />
              <span>Your Progress</span>
            </div>
          )}
          {mapData.user_position && (
            <div className="flex items-center gap-2">
              <div className="w-3 h-3 bg-red-500 rounded-full shadow-sm border border-white" />
              <span>Your Position</span>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
