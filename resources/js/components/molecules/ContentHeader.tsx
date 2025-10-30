import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import React from 'react';

type ButtonProps = {
  label: string;
  route: string;
  icon: React.ElementType;
  variant?: 'default' | 'outline' | 'outline-primary';
};

type ContentHeaderProps = {
  title: string;
  description?: string;
  actions?: ButtonProps[];
};

export default function ContentHeader({ title, description, actions }: ContentHeaderProps) {
  // Filter for only valid buttons (must have a label and a route)
  const validActions = actions?.filter((action) => action.label && action.route) || [];

  // Check if anything should be rendered at all
  if (!title && validActions.length === 0) {
    return null;
  }

  return (
    <div className="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      {title && (
        <div>
          <h1 className="text-2xl font-semibold">{title}</h1>
          {description && (
            <p className="text-muted-foreground">
              {description}
            </p>
          )}
        </div>
      )}
      {validActions.length > 0 && (
        <div className="flex gap-2">
          {validActions.map((action, index) => {
            const IconComponent = action.icon;

            return (
              <Link key={index} href={action.route}>
                <Button variant={action.variant || 'default'}>
                  {IconComponent && <IconComponent className="size-4" />}
                  {action.label}
                </Button>
              </Link>
            );
          })}
        </div>
      )}
    </div>
  );
}
