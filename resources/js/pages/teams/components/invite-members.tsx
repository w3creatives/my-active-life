import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Mail, Plus, X } from 'lucide-react';
import { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { type SharedData } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function InviteMembers() {
  const { errors } = usePage<SharedData>().props;
  const [emails, setEmails] = useState<string[]>(new Array(5).fill(''));

  // Clear form when component mounts or when there are no errors
  useEffect(() => {
    if (!errors || Object.keys(errors).length === 0) {
      setEmails(new Array(5).fill(''));
    }
  }, [errors]);

  const handleEmailChange = (index: number, value: string) => {
    const newEmails = [...emails];
    newEmails[index] = value;
    setEmails(newEmails);
  };

  const addEmailField = () => {
    if (emails.length < 20) { // Limit to 20 email fields
      setEmails([...emails, '']);
    } else {
      toast.info('Maximum 20 email addresses allowed at once.');
    }
  };

  const removeEmailField = (index: number) => {
    if (emails.length > 1) {
      const newEmails = emails.filter((_, i) => i !== index);
      setEmails(newEmails);
    }
  };

  const clearAllEmails = () => {
    setEmails(new Array(5).fill(''));
  };

  const handleInvite = () => {
    const validEmails = emails.filter(email => email.trim() !== '');
    if (validEmails.length === 0) {
      toast.error('Please enter at least one email address.');
      return;
    }
    
    router.post(route('teams.invite-members'), {
      emails: validEmails
    }, {
      preserveScroll: true,
      onSuccess: (response) => {
        // Clear the form after successful submission
        setEmails(new Array(5).fill(''));
        
        // Check for server-side alert message
        let alert = response.props.alert as { type: string; message: string } | undefined;
        if (alert) {
          if (alert.type === 'success') {
            toast.success(alert.message);
          } else {
            toast.error(alert.message);
          }
        } else {
          toast.success('Invitations sent successfully!');
        }
      },
      onError: (errors) => {
        // Handle validation errors for specific email fields
        const emailErrors = Object.keys(errors).filter(key => key.startsWith('emails.'));
        if (emailErrors.length > 0) {
          // Show the first email error
          const firstErrorKey = emailErrors[0];
          const errorMessage = errors[firstErrorKey];
          if (errorMessage) {
            toast.error(errorMessage);
          } else {
            toast.error('Failed to send invitations. Please try again.');
          }
        } else if (errors.emails) {
          // Handle general emails error
          toast.error(errors.emails[0] || 'Failed to send invitations. Please try again.');
        } else {
          toast.error('Failed to send invitations. Please try again.');
        }
      },
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>People to Invite</CardTitle>
        <CardDescription>Add email addresses to invite new members to your team. You can add as many as you need using the "Add Another Email" button below.</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-6">
          <div className="space-y-4">
            {emails.map((email, index) => {
              const fieldError = errors[`emails.${index}`];
              return (
                <div key={index} className="space-y-1">
                  <div className="relative">
                    <div className="left-0 absolute inset-y-0 flex items-center pl-3 pointer-events-none">
                      <Mail className={`h-4 w-4 ${fieldError ? 'text-red-400' : 'text-gray-400'}`} />
                    </div>
                    <Input
                      type="email"
                      placeholder="Email"
                      value={email}
                      onChange={(e) => handleEmailChange(index, e.target.value)}
                      className={`pl-10 pr-10 ${fieldError ? 'border-red-500 focus-visible:border-red-500' : ''}`}
                    />
                    {emails.length > 1 && (
                      <button
                        type="button"
                        onClick={() => removeEmailField(index)}
                        className="right-0 absolute inset-y-0 flex items-center pr-3 text-gray-400 hover:text-red-500 transition-colors"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                  {fieldError && (
                    <p className="text-red-600 text-sm">{fieldError}</p>
                  )}
                </div>
              );
            })}
          </div>

          <div className="flex justify-center gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={addEmailField}
              disabled={emails.length >= 20}
              className="flex items-center gap-2"
            >
              <Plus className="w-4 h-4" />
              Add Another Email ({emails.length}/20)
            </Button>
            {emails.some(email => email.trim() !== '') && (
              <Button
                type="button"
                variant="outline"
                onClick={clearAllEmails}
                className="flex items-center gap-2 text-gray-600"
              >
                Clear All
              </Button>
            )}
          </div>

          <Button 
            variant="default"
            onClick={handleInvite}
            className="w-full"
          >
            Invite New Members
          </Button>

          <div className="bg-gray-50 p-4 rounded-md text-gray-500 text-sm">
            <p>
              Just a heads up, only a team captain can approve requests to join the team. Any team member can invite new team members. When participants accept the invitation they will show up automagically in your team.
            </p>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}