import { Head } from '@inertiajs/react';
import { User, Users, Shield, Eye, EyeOff } from 'lucide-react';

import HeadingSmall from '@/components/heading-small';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Privacy settings',
    href: '/settings/privacy',
  },
];

/**
 * Privacy settings page component
 * Allows users to manage their profile visibility and team privacy settings
 */
export default function Privacy() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Privacy settings" />

      <SettingsLayout>
        <div className="space-y-8">
          {/* Page Header */}
          <div className="space-y-2">
            <HeadingSmall 
              title="Privacy Settings" 
              description="Manage your profile visibility and control who can see your fitness journey" 
            />
          </div>

          {/* Profile Visibility Section */}
          <Card className="border-0 shadow-sm">
            <CardHeader className="pb-4">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                  <User className="h-5 w-5" />
                </div>
                <div>
                  <CardTitle className="text-lg font-semibold">Profile Visibility</CardTitle>
                  <p className="text-sm text-muted-foreground">Control who can view your profile and follow your progress</p>
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* User Profile Card */}
              <div className="rounded-lg border bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
                <div className="flex items-center gap-4">
                  <div className="flex h-16 w-16 items-center justify-center rounded-full bg-blue-600 text-white">
                    <User className="h-8 w-8" />
                  </div>
                  <div className="flex-1">
                    <h3 className="text-lg font-semibold text-gray-900">Your Profile</h3>
                    <p className="text-sm text-gray-600">Your profile is set to private.</p>
                    <div className="mt-2 flex items-center gap-2">
                      <Badge variant="secondary" className="bg-gray-100 text-gray-700">
                        <EyeOff className="mr-1 h-3 w-3" />
                        Private
                      </Badge>
                    </div>
                  </div>
                  <div className="flex items-center space-x-3">
                    <Label htmlFor="profile-visibility" className="text-sm font-medium">
                      Make profile public
                    </Label>
                    <Switch id="profile-visibility" />
                  </div>
                </div>
              </div>

              {/* Privacy Information */}
              <div className="rounded-lg bg-amber-50 border border-amber-200 p-4">
                <div className="flex items-start gap-3">
                  <Shield className="h-5 w-5 text-amber-600 mt-0.5" />
                  <div className="text-sm">
                    <p className="font-medium text-amber-800">Privacy Notice</p>
                    <p className="text-amber-700 mt-1">
                      When your profile is public, other participants can follow your progress and see your achievements. 
                      Private profiles are only visible to you and approved followers.
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Team Privacy Section */}
          <Card className="border-0 shadow-sm">
            <CardHeader className="pb-4">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 text-green-600">
                  <Users className="h-5 w-5" />
                </div>
                <div>
                  <CardTitle className="text-lg font-semibold">Team Privacy</CardTitle>
                  <p className="text-sm text-muted-foreground">Manage your team's visibility and member access</p>
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Team Profile Card */}
              <div className="rounded-lg border bg-gradient-to-r from-green-50 to-emerald-50 p-6">
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">Test Team 2026</h3>
                      <p className="text-sm text-gray-600">Team Test Team 2026's profile is set to public.</p>
                    </div>
                    <Badge variant="default" className="bg-green-100 text-green-700">
                      <Eye className="mr-1 h-3 w-3" />
                      Public
                    </Badge>
                  </div>
                  
                  <div className="flex items-center justify-between pt-4 border-t border-green-200">
                    <div>
                      <Label htmlFor="team-visibility" className="text-sm font-medium">
                        Make team profile public?
                      </Label>
                      <p className="text-xs text-gray-500 mt-1">
                        Public teams appear in searches and allow others to follow progress
                      </p>
                    </div>
                    <div className="flex items-center gap-3">
                      <Button variant="outline" size="sm" className="text-green-600 border-green-300 hover:bg-green-50">
                        Yes
                      </Button>
                    </div>
                  </div>
                </div>
              </div>

              {/* Team Admin Notice */}
              <div className="rounded-lg bg-blue-50 border border-blue-200 p-4">
                <div className="flex items-start gap-3">
                  <Users className="h-5 w-5 text-blue-600 mt-0.5" />
                  <div className="text-sm">
                    <p className="font-medium text-blue-800">Team Admin Controls</p>
                    <p className="text-blue-700 mt-1">
                      If you are a team admin, you can also set team's visibility in this section. 
                      Team visibility settings affect how your team appears to other participants.
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Additional Privacy Options */}
          <Card className="border-0 shadow-sm">
            <CardHeader className="pb-4">
              <CardTitle className="text-lg font-semibold">Additional Privacy Options</CardTitle>
              <p className="text-sm text-muted-foreground">Fine-tune your privacy preferences</p>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between py-3 border-b border-gray-100">
                <div>
                  <Label className="text-sm font-medium">Allow followers to see detailed stats</Label>
                  <p className="text-xs text-gray-500 mt-1">Show detailed progress and achievement data to followers</p>
                </div>
                <Switch />
              </div>
              
              <div className="flex items-center justify-between py-3 border-b border-gray-100">
                <div>
                  <Label className="text-sm font-medium">Show activity feed</Label>
                  <p className="text-xs text-gray-500 mt-1">Display your recent activities to followers</p>
                </div>
                <Switch />
              </div>
              
              <div className="flex items-center justify-between py-3">
                <div>
                  <Label className="text-sm font-medium">Allow direct messages</Label>
                  <p className="text-xs text-gray-500 mt-1">Let other participants send you messages</p>
                </div>
                <Switch />
              </div>
            </CardContent>
          </Card>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
