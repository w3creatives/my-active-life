import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
interface DataSourceProps {
  items: any;
  title: string;
  type: string;
}

export default function DatasourcePoint({ items, title, type, handlePointChange }: DataSourceProps) {
  return (
    <>
      <div className="space-y-4">
        <h2 className="text-xl">{title}</h2>
        <hr />
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-1 md:grid-cols-2">
          {items.map((item) => (
            <div key={`${type}-${item.modality}`} className="space-y-1">
              <div className="flex items-center justify-between">
                <Label className="text-sm font-medium">{item.modality.charAt(0).toUpperCase() + item.modality.slice(1)}</Label>
              </div>

              <div className="grid gap-2">
                <Input type="number" step="0.01" defaultValue={item.points} className="w-full" disabled={type != 'manual'} min={0} onChange={(e) => handlePointChange(e.target.value,item)}/>
              </div>
            </div>
          ))}
        </div>
      </div>
    </>
  );
}
