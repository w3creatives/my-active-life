import { Head } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Privacy settings',
    href: '/settings/privacy',
  },
];

export default function Privacy() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Privacy settings" />

      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall title="Privacy settings" description="Update your account's privacy settings" />
        </div>
        <div className="space-y-6">
          <p>
            This area allows you to change your privacy options. If you set your profile to public, other event participants will be able to follow
            your progress on your fitness journey. By default, all profiles are set to private.
          </p>

          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <img
                  className="h-32 w-fit rounded-md"
                  src="https://rte-tracker.nyc3.digitaloceanspaces.com/x7zg285go92ln9dqbsyz4c7gpicb?response-content-disposition=inline%3B%20filename%3D%22the-heros-journey-header-image.png%22%3B%20filename%2A%3DUTF-8%27%27the-heros-journey-header-image.png&response-content-type=image%2Fpng&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=KZO6Z5DBFU7L3XZNBIJ7%2F20250510%2Fnyc3%2Fs3%2Faws4_request&X-Amz-Date=20250510T120708Z&X-Amz-Expires=300&X-Amz-SignedHeaders=host&X-Amz-Signature=1892a21b6fdee52a353ee9263ef17a61c03b07e994f85f3c7dff8f8b173e053d"
                  alt=""
                />
              </CardHeader>
              <CardContent>
                <h2 className="text-2xl">The Hero's Journey (June 2024)</h2>
                <p className="text-muted-foreground">Lorem ipsum dolor sit amet, consectetur adipisicing elit.</p>
              </CardContent>
              <CardFooter>
                <div className="flex items-center space-x-2">
                  <Switch id="enable-manual-entry" />
                  <Label htmlFor="enable-manual-entry">Make Manual Entry Global?</Label>
                </div>
              </CardFooter>
            </Card>
          </div>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
