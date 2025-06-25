import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

/**
 * Skeleton component for card-based content
 */
export function CardSkeleton({ showDescription = true }: { showDescription?: boolean }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>
          <Skeleton className="h-6 w-3/4" />
        </CardTitle>
        {showDescription && (
          <CardDescription>
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-2/3 mt-1" />
          </CardDescription>
        )}
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-5/6" />
          <Skeleton className="h-4 w-4/5" />
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Skeleton component for video cards (tutorials)
 */
export function VideoCardSkeleton() {
  return (
    <Card className="mb-6">
      <CardHeader>
        <CardTitle>
          <Skeleton className="h-7 w-3/4" />
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="relative w-full aspect-video">
          <Skeleton className="w-full h-full rounded-md" />
          <div className="absolute inset-0 flex items-center justify-center">
            <Skeleton className="rounded-full w-16 h-16" />
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Skeleton component for list items
 */
export function ListItemSkeleton() {
  return (
    <div className="flex items-center space-x-4 p-4 border rounded-lg">
      <Skeleton className="h-12 w-12 rounded-full" />
      <div className="space-y-2 flex-1">
        <Skeleton className="h-4 w-1/3" />
        <Skeleton className="h-3 w-1/2" />
      </div>
      <Skeleton className="h-8 w-20" />
    </div>
  );
}

/**
 * Skeleton component for team/user cards in follow page
 */
export function FollowCardSkeleton() {
  return (
    <Card>
      <CardHeader>
        <div className="flex items-center space-x-4">
          <Skeleton className="h-12 w-12 rounded-full" />
          <div className="space-y-2 flex-1">
            <Skeleton className="h-5 w-1/2" />
            <Skeleton className="h-4 w-3/4" />
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          <div className="flex justify-between">
            <Skeleton className="h-4 w-1/3" />
            <Skeleton className="h-4 w-1/4" />
          </div>
          <div className="flex justify-between">
            <Skeleton className="h-4 w-1/4" />
            <Skeleton className="h-4 w-1/3" />
          </div>
          <Skeleton className="h-8 w-full mt-4" />
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Skeleton component for statistics cards
 */
export function StatCardSkeleton() {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">
          <Skeleton className="h-4 w-24" />
        </CardTitle>
        <Skeleton className="h-4 w-4" />
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          <Skeleton className="h-8 w-20" />
          <Skeleton className="h-3 w-32" />
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Skeleton component for charts
 */
export function ChartSkeleton({ height = "h-64" }: { height?: string }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>
          <Skeleton className="h-6 w-1/3" />
        </CardTitle>
        <CardDescription>
          <Skeleton className="h-4 w-2/3" />
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className={`${height} w-full`}>
          <Skeleton className="w-full h-full rounded-md" />
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Skeleton component for data tables
 */
export function TableSkeleton({ rows = 5, columns = 4 }: { rows?: number; columns?: number }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>
          <Skeleton className="h-6 w-1/4" />
        </CardTitle>
        <CardDescription>
          <Skeleton className="h-4 w-1/2" />
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          {/* Table header */}
          <div className="grid gap-4" style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}>
            {Array.from({ length: columns }).map((_, i) => (
              <Skeleton key={`header-${i}`} className="h-4 w-full" />
            ))}
          </div>
          {/* Table rows */}
          {Array.from({ length: rows }).map((_, rowIndex) => (
            <div key={`row-${rowIndex}`} className="grid gap-4" style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}>
              {Array.from({ length: columns }).map((_, colIndex) => (
                <Skeleton key={`cell-${rowIndex}-${colIndex}`} className="h-4 w-full" />
              ))}
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Grid of skeleton cards
 */
export function SkeletonGrid({ 
  count = 6, 
  columns = 2, 
  component: Component = CardSkeleton 
}: { 
  count?: number; 
  columns?: number; 
  component?: React.ComponentType<any>;
}) {
  return (
    <div className={`grid grid-cols-1 lg:grid-cols-${columns} gap-6`}>
      {Array.from({ length: count }).map((_, index) => (
        <Component key={index} />
      ))}
    </div>
  );
}
