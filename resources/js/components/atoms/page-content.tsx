import EventBannerImage from '@/components/atoms/EventBannerImage';
import React from 'react';

export default function PageContent({ children, showEventBannerImage = true }: { children: React.ReactNode; showEventBannerImage?: boolean }) {
  return (
    <div className="flex min-h-svh flex-col gap-6 p-4">
      {showEventBannerImage && <EventBannerImage />}
      {children}
    </div>
  );
}
