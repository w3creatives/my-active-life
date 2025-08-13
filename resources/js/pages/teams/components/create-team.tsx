import { type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

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

export default function CreateTeam({status }: {  status?: string }) {
  const { auth } = usePage<SharedData>().props;

  const { data, setData, patch, errors, processing, recentlySuccessful } = useForm<ProfileForm>({
    name: auth.user.name,
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
    <div className="space-y-6">
      <HeadingSmall title="Create Your Team" description="What do you want your team to be named?" />

      <form onSubmit={submit} className="space-y-6">
        <div className="grid gap-2">
          <Label htmlFor="name">Name</Label>
          <Input
            id="name"
            type="name"
            className="mt-1 block w-full"
            value={data.name}
            onChange={(e) => setData('name', e.target.value)}
            required
            autoComplete="name"
            placeholder="Enter Your Run The Month Imagine Communications August-2025 Team Name"
          />
          <InputError className="mt-2" message={errors.email} />
        </div>

        <HeadingSmall title="Would you like to spice things up?" description='Set a goal for your team to double, triple, quadruple...all the way up to 10X the miles for the year! Each "Chutzpah Factor" increases your team mileage goal by 100 miles and changes the stats you will see as your team embarks on your journey. How far can you go?!' />

          <HeadingSmall title="To be, or not to be...public?" description="Choose to make your team public or private. Public teams will show up in searches and allow others to follow your progress. Private teams will be invisible from others." />
        <div className="flex items-center gap-4">
          <Button disabled={processing}>Create Team</Button>

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
  );
}
