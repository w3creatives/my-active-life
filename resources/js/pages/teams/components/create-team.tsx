import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { Switch } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { toast } from 'sonner';
import AdminControls from './admin-controls';
import React from 'react';

type CreateTeamForm = {
  name: string;
  chutzpah_factor: number;
  public_profile: boolean;
  teamId: any;
};

export default function CreateTeam({ status }: { status?: string }) {
  // @ts-ignore
  const { team, chutzpahFactorUnit, teamPublicProfile } = usePage<SharedData>().props;
  const teamData = team as any; // Type assertion to avoid TypeScript errors
  // @ts-ignore
  const { data, setData, post, errors, processing, reset, recentlySuccessful } = useForm<CreateTeamForm>({
    chutzpah_factor: chutzpahFactorUnit,
    name: teamData?.name || '',
    public_profile: teamPublicProfile,
    teamId: teamData?.id || null,
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    post(route('teams.create'), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: (response) => {
        let alert = response.props.alert;

        // @ts-ignore
        if (alert.type == 'success') {
          // @ts-ignore
          toast.success(alert.message);
        } else {
          // @ts-ignore
          toast.error(alert.message);
        }
        reset();
      },
      onError: (errors) => {
        console.log(errors);
        if (errors.event_id) {
          toast.error(errors.event_id);
          // reset('password', 'password_confirmation');
          //passwordInput.current?.focus();
        }
      },
    });
  };

  return (
    <>
      <Card>
        <CardHeader>
          {!teamData ? <CardTitle>Create Your Team</CardTitle> : <CardTitle>Rename Your Team</CardTitle>}
          {!teamData ? <CardDescription>What do you want your team to be named?</CardDescription> : <CardDescription>What do you want your team to be named?</CardDescription>}
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="space-y-6">
            <div className="gap-2 grid">
              <Input
                id="name"
                type="name"
                className="block mt-1 w-full"
                onChange={(e) => setData('name', e.target.value)}
                autoComplete="name"
                placeholder="Enter Your Run The Month Imagine Communications August-2025 Team Name"
                value={data.name}
              />
              <InputError className="mt-2" message={errors.name} />
            </div>

            <HeadingSmall
              title={teamData ? 'Change Team Chutzpah Factor' : 'Would you like to spice things up?'}
              description='Set a goal for your team to double, triple, quadruple...all the way up to 10X the miles for the year! Each "Chutzpah Factor" increases your team mileage goal by 100 miles and changes the stats you will see as your team embarks on your journey. How far can you go?!'
            />
            <div className="gap-2 grid">
              Chutzpah Factor = {data.chutzpah_factor}
              <input
                onChange={(e) => {
                  // @ts-ignore
                  setData('chutzpah_factor', e.target.value);
                }}
                value={data.chutzpah_factor}
                type="range"
                min="1"
                max="10"
              />
              Your team will need to complete {data.chutzpah_factor * 100} miles.
            </div>
            <HeadingSmall
              title={teamData ? 'Change Team Visibility' : 'To be, or not to be...public?'}
              description="Choose to make your team public or private. Public teams will show up in searches and allow others to follow your progress. Private teams will be invisible from others."
            />
            <div className="gap-2 grid">
              {!teamData ? <Label>Make this team's profile public?</Label> : ''}
              <Switch
                defaultChecked={data.public_profile}
                onChange={(e) => {
                  // @ts-ignore
                  setData('public_profile', !data.public_profile);
                }}
                className="group inline-flex items-center bg-gray-200 data-checked:bg-blue-600 data-disabled:opacity-50 rounded-full w-11 h-6 data-disabled:cursor-not-allowed"
              >
                <span className="bg-white rounded-full size-4 transition translate-x-1 group-data-checked:translate-x-6" />
              </Switch>
            </div>

            <div className="flex items-center gap-4">
              <Button disabled={processing}>{teamData ? 'Update' : 'Create'} Team</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </>
  );
}
