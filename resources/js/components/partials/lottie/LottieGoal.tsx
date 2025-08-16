import lottie from 'lottie-web';
import { useEffect, useRef } from 'react';

type LottieGoalProps = {
  currentPoints: number;
  goal: number;
};

export function LottieGoal({ currentPoints, goal }: LottieGoalProps) {
  const lottieContainer = useRef<HTMLDivElement>(null);

  // Calculate percentage (clamped between 0 and 100)
  const progressPercentage = Math.min(100, Math.max(0, (currentPoints / goal) * 100));

  useEffect(() => {
    if (!lottieContainer.current) return;

    const animation = lottie.loadAnimation({
      container: lottieContainer.current,
      renderer: 'svg',
      loop: false,
      autoplay: false,
      path: './Turt_Russell.json',
    });

    animation.addEventListener('DOMLoaded', () => {
      const p = progressPercentage;
      let frame = 0.286 + 0.821 * p + 0.00583 * Math.pow(p, 2);
      frame = frame < 1 ? 1 : frame;

      animation.playSegments([0, parseInt(frame.toString(), 10)], true);
    });

    return () => {
      animation.destroy();
    };
  }, [progressPercentage]);

  const gradientStyle = {
    backgroundImage: `linear-gradient(to right, rgba(44, 214, 242, 1) 0%, rgba(44, 214, 242, 1) ${progressPercentage}%, rgba(51, 51, 51, 1) ${progressPercentage}%, rgba(51, 51, 51, 1) 100%)`,
  };

  return (
    <div className="space-y-4 text-center">
      <p className="m-0 inline-block bg-clip-text font-mono text-xl font-bold text-transparent" style={gradientStyle}>
        2025 miles in 2025!
      </p>
      <div ref={lottieContainer} className="mx-auto w-full" />
    </div>
  );
}
