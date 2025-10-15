import { clsx } from 'clsx';

interface headingProps {
    title: string;
    description?: string;
    level?: 1 | 2 | 3 | 4 | 5 | 6;
    headingSize?: string;
}

export default function PageTitle({ title, description, level = 1, headingSize = '3xl' }: headingProps) {
    const Tag: React.ElementType = (`h${level}`) as unknown as React.ElementType;


    return (
        <div className="mb-4 space-y-0.5">
            <Tag className={clsx(`font-normal tracking-tight`, `text-${headingSize}`)}>{title}</Tag>
            {description && <p className="text-muted-foreground text-sm">{description}</p>}
        </div>
    );
}
