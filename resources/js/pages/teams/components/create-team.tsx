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
import TeamInvitations from './team-invitations';
import { CheckCircle2 } from 'lucide-react';

type CreateTeamForm = {
  name: string;
  chutzpah_factor: number;
  public_profile: boolean;
  teamId: any;
};

export default function CreateTeam({ status }: { status?: string }) {
  // @ts-ignore
  const { auth } = usePage().props as any;
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
        const alert = response.props.alert;

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
      {/* Team Invitations Section - Show above create team if user has invitations */}
      <TeamInvitations />

      <Card>
        <CardHeader>
          {!teamData ? <CardTitle>Create Your Team</CardTitle> : <CardTitle>Rename Your Team</CardTitle>}
          {!teamData ? (
            <CardDescription>What do you want your team to be named?</CardDescription>
          ) : (
            <CardDescription>What do you want your team to be named?</CardDescription>
          )}
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-2">
              <Input
                id="name"
                type="name"
                className="mt-1 block w-full"
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
            <div className="space-y-2">
              <Label className="text-sm font-medium block">Chutzpah Factor = <strong>{data.chutzpah_factor}x</strong></Label>
              <div className="flex flex-wrap gap-3">
                {Array.from({ length: 10 }, (_, i) => i + 1).map((factor) => (
                  <div
                    key={factor}
                    onClick={() => setData('chutzpah_factor', factor)}
                    className={`relative cursor-pointer rounded-lg border-2 px-4 py-3 transition-all duration-200 hover:shadow-sm flex-1 max-w-25 ${data.chutzpah_factor === factor
                        ? 'border-primary'
                        : 'border-border hover:border-primary/50'
                      }`}
                  >
                    {data.chutzpah_factor === factor && (
                      <div className="absolute -top-1 -right-1">
                        <CheckCircle2 className="text-primary bg-background size-4 rounded-full" />
                      </div>
                    )}
                    <div className="text-center">
                      <div className="text-primary text-2xl font-semibold">
                        {factor}
                        <small>x</small>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              <p>Your team will need to complete <strong>{data.chutzpah_factor * auth.preferred_event?.total_points} miles</strong>.</p>
            </div>
            <HeadingSmall
              title={teamData ? 'Change Team Visibility' : 'To be, or not to be...public?'}
              description="Choose to make your team public or private. Public teams will show up in searches and allow others to follow your progress. Private teams will be invisible from others."
            />
            <div className="grid gap-2">
              {!teamData ? <Label>Make this team's profile public?</Label> : ''}
              <Switch
                defaultChecked={data.public_profile}
                onChange={(e) => {
                  // @ts-ignore
                  setData('public_profile', !data.public_profile);
                }}
                className="group inline-flex h-6 w-11 items-center rounded-full bg-gray-200 data-checked:bg-blue-600 data-disabled:cursor-not-allowed data-disabled:opacity-50"
              >
                <span className="size-4 translate-x-1 rounded-full bg-white transition group-data-checked:translate-x-6" />
              </Switch>
            </div>

            <div className="flex items-center gap-4">
              <Button disabled={processing}>{teamData ? 'Update' : 'Create'} Team</Button>
            </div>
          </form>
        </CardContent>
      </Card>

      {/* Admin Controls - Show only if user is team owner */}
      {teamData && <AdminControls />}
    </>
  );
}
