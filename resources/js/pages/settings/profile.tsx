import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: route('dashboard'),
  },
  {
    title: 'Settings',
    href: route('profile.edit'),
  },
  {
    title: 'Profile',
    href: route('profile.edit'),
  },
];

type ProfileForm = {
  email: string;
  first_name: string;
  last_name: string;
  display_name: string;
  bio: string | null;
  time_zone: string;
  gender: string | null;
  birthday: string | null;
};

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
  const { auth } = usePage<SharedData>().props;

  const { data, setData, patch, errors, processing, recentlySuccessful } = useForm<ProfileForm>({
    email: auth.user.email,
    first_name: auth.user.first_name || '',
    last_name: auth.user.last_name || '',
    display_name: auth.user.display_name || '',
    bio: auth.user.bio || '',
    time_zone: auth.user.time_zone || 'UTC',
    gender: auth.user.gender || '',
    birthday: auth.user.birthday || '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    patch(route('profile.update'), {
      preserveScroll: true,
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Profile settings" />

      <SettingsLayout>
        <div className="space-y-6">
          <HeadingSmall title="Profile information" description="Update your profile information" />

          <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-2">
              <Label htmlFor="email">Your Email</Label>
              <Input
                id="email"
                type="email"
                className="mt-1 block w-full"
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
                required
                autoComplete="username"
                placeholder="Your Email"
              />
              <InputError className="mt-2" message={errors.email} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="first_name">First Name</Label>
              <Input
                id="first_name"
                className="mt-1 block w-full"
                value={data.first_name}
                onChange={(e) => setData('first_name', e.target.value)}
                required
                placeholder="First name"
              />
              <InputError className="mt-2" message={errors.first_name} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="last_name">Last Name</Label>
              <Input
                id="last_name"
                className="mt-1 block w-full"
                value={data.last_name}
                onChange={(e) => setData('last_name', e.target.value)}
                required
                placeholder="Last name"
              />
              <InputError className="mt-2" message={errors.last_name} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="display_name">Display Name</Label>
              <Input
                id="display_name"
                className="mt-1 block w-full"
                value={data.display_name}
                onChange={(e) => setData('display_name', e.target.value)}
                required
                placeholder="Display name"
              />
              <InputError className="mt-2" message={errors.display_name} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="bio">Your Bio</Label>
              <Textarea
                id="bio"
                className="mt-1 block min-h-24 w-full"
                value={data.bio}
                onChange={(e) => setData('bio', e.target.value)}
                placeholder="Your bio, should you want to make your profile public and share info about you. For eample, you may want to share that you are a cat lover. Totally optional."
                rows={4}
              />
              <InputError className="mt-2" message={errors.bio} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="time_zone">Timezone</Label>
              <Select value={data.time_zone} onValueChange={(value) => setData('time_zone', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select timezone" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="UTC">UTC</SelectItem>
                  <SelectItem value="America/New_York">Eastern Time (ET)</SelectItem>
                  <SelectItem value="America/Chicago">Central Time (CT)</SelectItem>
                  <SelectItem value="America/Denver">Mountain Time (MT)</SelectItem>
                  <SelectItem value="America/Los_Angeles">Pacific Time (PT)</SelectItem>
                  {/* Add more timezones as needed */}
                </SelectContent>
              </Select>
              <InputError className="mt-2" message={errors.time_zone} />
            </div>

            <HeadingSmall title="Personal information" description="Additional details about you" />

            <div className="grid gap-2">
              <Label htmlFor="gender">Gender</Label>
              <Select value={data.gender} onValueChange={(value) => setData('gender', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select gender" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="male">Male</SelectItem>
                  <SelectItem value="female">Female</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                  <SelectItem value="none_of_your_beeswax">Prefer not to say</SelectItem>
                </SelectContent>
              </Select>
              <InputError className="mt-2" message={errors.gender} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="birthday">Birthday</Label>
              <Input
                id="birthday"
                type="date"
                className="mt-1 block w-full"
                value={data.birthday}
                onChange={(e) => setData('birthday', e.target.value)}
                placeholder="YYYY-MM-DD"
              />
              <InputError className="mt-2" message={errors.birthday} />
            </div>

            {/* Remove the avatar field section */}
            {/* <div className="grid gap-2">
                            <Label htmlFor="avatar">Select an Avatar</Label>
                            <Input
                                id="avatar"
                                type="file"
                                accept="image/*"
                                className="mt-1 block w-full"
                                onChange={handleFileChange}
                            />
                            <InputError className="mt-2" message={errors.avatar} />
                        </div> */}

            {mustVerifyEmail && auth.user.email_verified_at === null && (
              <div>
                <p className="text-muted-foreground -mt-4 text-sm">
                  Your email address is unverified.{' '}
                  <Link
                    href={route('verification.send')}
                    method="post"
                    as="button"
                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                  >
                    Click here to resend the verification email.
                  </Link>
                </p>

                {status === 'verification-link-sent' && (
                  <div className="mt-2 text-sm font-medium text-green-600">A new verification link has been sent to your email address.</div>
                )}
              </div>
            )}

            <div className="flex items-center gap-4">
              <Button disabled={processing}>Save</Button>

              <Transition
                show={recentlySuccessful}
                enter="transition ease-in-out"
                enterFrom="opacity-0"
                leave="transition ease-in-out"
                leaveTo="opacity-0"
              >
                <p className="text-sm text-neutral-600">Saved</p>
              </Transition>
            </div>
          </form>
        </div>

        <DeleteUser />
      </SettingsLayout>
    </AppLayout>
  );
}
