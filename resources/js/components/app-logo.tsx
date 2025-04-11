import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex size-8 items-center justify-center">
                <AppLogoIcon className="text-primary size-8 fill-current dark:text-white" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">Run The Edge</span>
            </div>
        </>
    );
}
