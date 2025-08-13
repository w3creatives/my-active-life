import React from 'react';
import EventBannerImage from '@/components/atoms/EventBannerImage';

export default function PageContent({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex flex-col gap-6 p-4 min-h-svh">
      <EventBannerImage />
      {children}
    </div>
  );
}
