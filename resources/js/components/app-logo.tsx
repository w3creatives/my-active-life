import { clsx } from 'clsx';
import AppLogoIcon from './app-logo-icon';

interface AppLogoProps {
  className?: string;
  logoColorLight?: string;
  logoColorDark?: string;
  logoSize?: number;
}

export default function AppLogo({ className, logoColorLight = 'primary', logoColorDark = 'white', logoSize = 8 }: AppLogoProps) {
  return (
    <>
      <div
        className={clsx('flex items-center justify-center', `size-${logoSize}`, `text-${logoColorLight}`, `dark:text-${logoColorDark}`, className)}
      >
        <AppLogoIcon className="fill-current" />
      </div>
    </>
  );
}
