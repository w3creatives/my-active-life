import EventBannerImage from '@/components/atoms/EventBannerImage';
import React from 'react';

export default function PageContent({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-svh flex-col gap-6 p-4">
      <EventBannerImage />
      {children}
    </div>
  );
}
