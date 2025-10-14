import { Facebook, Twitter, Linkedin, Download, Printer, Link2, Instagram } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface SocialShareButtonsProps {
  title: string;
  url: string;
  imageUrl?: string;
  className?: string;
}

export function SocialShareButtons({ title, url, imageUrl, className = '' }: SocialShareButtonsProps) {
  const encodedTitle = encodeURIComponent(title);
  const encodedUrl = encodeURIComponent(url);
  const encodedImage = imageUrl ? encodeURIComponent(imageUrl) : '';

  const shareLinks = {
    twitter: `https://twitter.com/intent/tweet?text=${encodedTitle}&url=${encodedUrl}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}&quote=${encodedTitle}`,
    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
    pinterest: imageUrl
      ? `https://pinterest.com/pin/create/button/?url=${encodedUrl}&media=${encodedImage}&description=${encodedTitle}`
      : null,
  };

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(url);
      // You could add a toast notification here
      alert('Link copied to clipboard!');
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  const handleDownload = async () => {
    if (!imageUrl) return;

    try {
      const response = await fetch(imageUrl);
      const blob = await response.blob();
      const blobUrl = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = blobUrl;
      link.download = `milestone-${Date.now()}.png`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(blobUrl);
    } catch (err) {
      console.error('Failed to download:', err);
    }
  };

  const handlePrint = () => {
    window.print();
  };

  const handleNativeShare = async () => {
    if (navigator.share) {
      try {
        await navigator.share({
          title: title,
          url: url,
        });
      } catch (err) {
        if ((err as Error).name !== 'AbortError') {
          console.error('Share failed:', err);
        }
      }
    } else {
      handleCopyLink();
    }
  };

  return (
    <div className={`flex flex-wrap gap-2 ${className}`}>
      {/* Twitter */}
      <Button
        variant="outline"
        size="icon"
        asChild
        className="hover:bg-[#1DA1F2] hover:text-white hover:border-[#1DA1F2]"
        title="Share on Twitter"
      >
        <a href={shareLinks.twitter} target="_blank" rel="noopener noreferrer">
          <Twitter className="h-4 w-4" />
        </a>
      </Button>

      {/* Facebook */}
      <Button
        variant="outline"
        size="icon"
        asChild
        className="hover:bg-[#1877F2] hover:text-white hover:border-[#1877F2]"
        title="Share on Facebook"
      >
        <a href={shareLinks.facebook} target="_blank" rel="noopener noreferrer">
          <Facebook className="h-4 w-4" />
        </a>
      </Button>

      {/* LinkedIn */}
      <Button
        variant="outline"
        size="icon"
        asChild
        className="hover:bg-[#0A66C2] hover:text-white hover:border-[#0A66C2]"
        title="Share on LinkedIn"
      >
        <a href={shareLinks.linkedin} target="_blank" rel="noopener noreferrer">
          <Linkedin className="h-4 w-4" />
        </a>
      </Button>

      {/* Copy Link */}
      <Button
        variant="outline"
        size="icon"
        onClick={handleCopyLink}
        className="hover:bg-gray-700 hover:text-white hover:border-gray-700"
        title="Copy link"
      >
        <Link2 className="h-4 w-4" />
      </Button>

      {/* Download Image */}
      {imageUrl && (
        <Button
          variant="outline"
          size="icon"
          onClick={handleDownload}
          className="hover:bg-green-600 hover:text-white hover:border-green-600"
          title="Download image"
        >
          <Download className="h-4 w-4" />
        </Button>
      )}

      {/* Print */}
      <Button
        variant="outline"
        size="icon"
        onClick={handlePrint}
        className="hover:bg-purple-600 hover:text-white hover:border-purple-600"
        title="Print"
      >
        <Printer className="h-4 w-4" />
      </Button>
    </div>
  );
}
