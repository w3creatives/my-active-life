import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

export default function EventBannerImage() {
  const { auth } = usePage<SharedData>().props;

  return(
    <div className="mb-4">
      <img src={auth.preferred_event.logo_url} alt={auth.preferred_event.name} className="w-full"></img>
    </div>
  )
}
