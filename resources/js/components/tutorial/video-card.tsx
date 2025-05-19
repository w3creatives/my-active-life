import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Play } from 'lucide-react';

type VideoProps = {
  source: string;
  title?: string;
  thumb?: string;
  url: string;
};

export default function VideoCard({ source, title, thumb, url }: VideoProps) {
  const [isOpen, setIsOpen] = useState(false);

  // Function to get the appropriate embed URL based on video source
  const getEmbedUrl = () => {
    if (source === 'vimeo') {
      // Extract the video ID from the Vimeo URL
      const vimeoId = url.split('/').pop();
      return `https://player.vimeo.com/video/${vimeoId}?autoplay=1`;
    } else if (source === 'youtube') {
      // Extract the video ID from YouTube URL
      const youtubeId = url.includes('youtu.be')
        ? url.split('/').pop()
        : url.split('v=')[1]?.split('&')[0];
      return `https://www.youtube.com/embed/${youtubeId}?autoplay=1`;
    }
    // Default: return the original URL if source is unknown
    return url;
  };

  // Get thumbnail image
  const getThumbnail = () => {
    if (thumb) return thumb;

    // Generate default thumbnail if none provided
    if (source === 'vimeo') {
      const vimeoId = url.split('/').pop();
      return `https://vumbnail.com/${vimeoId}.jpg`;
    } else if (source === 'youtube') {
      const youtubeId = url.includes('youtu.be')
        ? url.split('/').pop()
        : url.split('v=')[1]?.split('&')[0];
      return `https://img.youtube.com/vi/${youtubeId}/hqdefault.jpg`;
    }

    // Fallback image
    return 'https://placehold.co/600x400?text=Video';
  };

  return (
    <>
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>
            <h2 className="text-2xl font-normal">{title}</h2>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div
            className="relative w-full aspect-video cursor-pointer group"
            onClick={() => setIsOpen(true)}
          >
            <img
              src={getThumbnail()}
              alt={title}
              className="w-full h-full object-cover rounded-md"
            />
            <div className="absolute inset-0 flex items-center justify-center bg-black/50">
              <div className="bg-primary text-primary-foreground rounded-full p-4 transform transition-transform duration-300 group-hover:scale-120">
                <Play size={24} />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Dialog open={isOpen} onOpenChange={setIsOpen}>
        <DialogContent className="sm:max-w-5xl p-0">
          <DialogHeader className="p-4">
            <DialogTitle>
              <h2 className="text-2xl font-normal">{title}</h2>
            </DialogTitle>
          </DialogHeader>
          <div className="w-full aspect-video">
            {isOpen && (
              <iframe
                className="w-full h-full"
                src={getEmbedUrl()}
                frameBorder="0"
                allow="autoplay; fullscreen; picture-in-picture"
                allowFullScreen
              ></iframe>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
