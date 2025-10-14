import { clsx } from 'clsx';

interface headingProps {
  title: string;
  description?: string;
  headingSize?: string;
}

export default function Heading({ title, description , headingSize = 'xl' }: headingProps) {
  return (
    <div className="mb-8 space-y-0.5">
      <h2 className={clsx(`font-semibold tracking-tight`, `text-${headingSize}`)}>{title}</h2>
      {description && <p className="text-muted-foreground text-sm">{description}</p>}
    </div>
  );
}
