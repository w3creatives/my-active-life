import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export default function EventBannerImage() {
  const { auth } = usePage<SharedData>().props;

  return (
    <>
      {auth.preferred_event && (
        <div className="mb-4">
          <img src={auth.preferred_event.logo_url} alt={auth.preferred_event.name} className="max-h-[280px]" />
        </div>
      )}
    </>
  );
}
