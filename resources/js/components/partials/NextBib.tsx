import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function NextBib() {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-xl">Next Bib</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="flex flex-col items-center gap-5">
          <img src="/static/Logo-Amerithon.png" className="h-auto w-75 object-contain" alt="" onError={(e) => {e.currentTarget.src="/images/default-placeholder.png";}}/>
          <h3 className="text-2xl font-semibold">RTY 2025 Mile 1500</h3>
        </div>
      </CardContent>
    </Card>
  );
}
