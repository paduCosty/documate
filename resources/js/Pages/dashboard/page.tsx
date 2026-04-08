import { Link } from "@inertiajs/react";
import {
  Zap, Files, HardDrive, CreditCard, FileText, Download
} from "lucide-react";

import { AppLayout } from "@/components/documate/app-layout";
import { DocumateCard } from "@/components/documate/documate-card";
import { DocumateBadge } from "@/components/documate/documate-badge";
import { DocumateButton } from "@/components/documate/documate-button";

type IconComponent = React.ComponentType<{ className?: string }>;

type Stat = {
  icon: IconComponent;
  label: string;
  value: string | number;
  subtext: string;
  color?: string;
  isLink?: boolean;
};

const TOOL_CONFIG: Record<string, { label: string; color: string }> = {
  'merge-pdf':    { label: 'Merge PDF',    color: 'bg-blue-500/20 text-blue-400'   },
  'compress-pdf': { label: 'Compress PDF', color: 'bg-green-500/20 text-green-400' },
  'split-pdf':    { label: 'Split PDF',    color: 'bg-orange-500/20 text-orange-400'},
  'word-to-pdf':  { label: 'Word to PDF',  color: 'bg-red-500/20 text-red-400'     },
  'excel-to-pdf': { label: 'Excel to PDF', color: 'bg-yellow-500/20 text-yellow-400'},
  'ppt-to-pdf':   { label: 'PPT to PDF',   color: 'bg-purple-500/20 text-purple-400'},
  'pdf-to-jpg':   { label: 'PDF to JPG',   color: 'bg-pink-500/20 text-pink-400'   },
};

function getToolConfig(tool: string) {
  const key = tool.replace(/_/g, '-');
  return TOOL_CONFIG[key] ?? { label: tool, color: 'bg-zinc-500/20 text-zinc-400' };
}

type RecentFile = {
  id: number;
  uuid: string;
  name: string;
  tool: string;
  size: string;
  date: string;
  expires: string;
  isExpired?: boolean;
  canDownload?: boolean;
  awaitingPayment?: boolean;
};

type Usage = {
  daily_used: number;
  daily_limit: number;
  percentage: number;
  resets_at: string;
};

type DashboardProps = {
  user?: { name: string };
  stats?: Stat[];
  recentFiles?: RecentFile[];
  usage?: Usage;
  hasActiveSubscription?: boolean;
  creditBalance?: number;
};

export default function DashboardPage({
  user,
  stats = [],
  recentFiles = [],
  usage = { daily_used: 0, daily_limit: 3, percentage: 0, resets_at: "midnight UTC" },
  hasActiveSubscription = false,
  creditBalance = 0,
}: DashboardProps) {

  const getGreeting = () => {
    const h = new Date().getHours();
    if (h >= 5  && h < 12) return "Good morning";
    if (h >= 12 && h < 17) return "Good afternoon";
    if (h >= 17 && h < 21) return "Good evening";
    return "Good night";
  };

  const iconMap: Record<string, IconComponent> = { Zap, Files, HardDrive, CreditCard };

  // Determine badge to show next to user name
  const planBadge = hasActiveSubscription ? "Pro" : creditBalance > 0 ? "Credits" : "Free";

  return (
    <AppLayout>
      <div className="px-8 py-10">

        {/* Header */}
        <div className="flex items-center justify-between">
          <h2 className="text-2xl font-semibold text-white">
            {getGreeting()}, {user?.name || "User"}
          </h2>
          <div className="flex items-center gap-3">
            <DocumateBadge variant={hasActiveSubscription ? "success" : creditBalance > 0 ? "warning" : undefined}>
              {planBadge}
            </DocumateBadge>
            {!hasActiveSubscription && (
              <Link href="/dashboard/billing">
                <DocumateButton variant="outline" size="sm">Upgrade</DocumateButton>
              </Link>
            )}
          </div>
        </div>

        {/* Stats Grid */}
        <div className="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
          {stats.map((stat, index) => {
            const IconComponent = iconMap[stat.icon as string] || Zap;
            return (
              <DocumateCard key={index} padding="sm" hover>
                <div className="flex items-start justify-between">
                  <IconComponent className={`h-5 w-5 ${stat.color || "text-zinc-600"}`} />
                </div>
                <p className="mt-3 text-xs text-zinc-500">{stat.label}</p>
                <p className="mt-1 text-xl font-semibold text-white">{stat.value}</p>
                {stat.isLink ? (
                  <Link href="/dashboard/billing" className="text-xs text-zinc-400 transition-colors hover:text-white">
                    {stat.subtext}
                  </Link>
                ) : (
                  <p className="text-xs text-zinc-500">{stat.subtext}</p>
                )}
              </DocumateCard>
            );
          })}
        </div>

        {/* Usage / Credits / Pro status */}
        {hasActiveSubscription ? (
          <DocumateCard className="mt-4 border-green-700/50 bg-green-950/20">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="rounded-full bg-green-500/20 p-2">
                  <Zap className="h-4 w-4 text-green-400" />
                </div>
                <span className="text-sm font-medium text-white">Pro Plan Active</span>
              </div>
              <span className="text-sm font-medium text-green-400">Unlimited</span>
            </div>
            <p className="mt-3 text-xs text-zinc-400">
              Enjoy unlimited PDF operations, priority processing, and 100MB file uploads.
            </p>
          </DocumateCard>
        ) : (
          <>
            {/* Daily free operations bar */}
            <DocumateCard className="mt-4">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-white">Free operations today</span>
                <span className="text-sm text-zinc-500">
                  {usage.daily_used} of {usage.daily_limit} used
                </span>
              </div>
              <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-800">
                <div
                  className="h-full rounded-full bg-white transition-all"
                  style={{ width: `${Math.min(usage.percentage, 100)}%` }}
                />
              </div>
              <p className="mt-2 text-xs text-zinc-600">
                {creditBalance > 0
                  ? "Credits are used automatically once free ops run out · Resets at " + usage.resets_at
                  : "Resets at " + usage.resets_at}
              </p>
            </DocumateCard>

            {/* Credit balance card — shown when user has any credits */}
            {creditBalance > 0 && (
              <DocumateCard className="mt-3 border-amber-700/40 bg-amber-950/10">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="rounded-full bg-amber-500/20 p-2">
                      <Zap className="h-4 w-4 text-amber-400" />
                    </div>
                    <div>
                      <span className="text-sm font-medium text-white">Credit balance</span>
                      <p className="text-xs text-zinc-500">
                        Charged automatically after your 3 free daily ops
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <span className="text-2xl font-bold text-amber-400">{creditBalance}</span>
                    <p className="text-xs text-zinc-500">remaining</p>
                  </div>
                </div>
                <div className="mt-3 flex items-center justify-between border-t border-amber-900/30 pt-3">
                  <span className="text-xs text-zinc-600">Credits never expire</span>
                  <Link href="/pricing" className="text-xs font-medium text-amber-500 hover:text-amber-400 transition-colors">
                    Buy more →
                  </Link>
                </div>
              </DocumateCard>
            )}
          </>
        )}

        {/* Recent Files */}
        <div className="mt-8">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-white">Recent files</h3>
            <Link href="/dashboard/files" className="text-sm text-zinc-500 transition-colors hover:text-white">
              View all &rarr;
            </Link>
          </div>

          <DocumateCard className="mt-4 overflow-hidden p-0">
            <div className="grid grid-cols-[1fr_100px_80px_100px_100px_50px] gap-4 bg-zinc-800/50 px-6 py-3 text-xs font-medium text-zinc-500">
              <span>File</span><span>Tool</span><span>Size</span><span>Date</span><span>Expires</span><span></span>
            </div>

            {recentFiles.length > 0 ? (
              recentFiles.map((file) => {
                const toolCfg = getToolConfig(file.tool);
                return (
                  <div
                    key={file.id}
                    className="grid grid-cols-[1fr_100px_80px_100px_100px_50px] gap-4 border-t border-zinc-800 px-6 py-4 transition-colors hover:bg-zinc-800/30"
                  >
                    <div className="flex items-center gap-3">
                      <FileText className="h-3.5 w-3.5 flex-shrink-0 text-red-400" />
                      <span className="truncate text-sm text-white">
                        {file.name.length > 50 ? `${file.name.substring(0, 40)}...` : file.name}
                      </span>
                    </div>
                    <span className={`inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs ${toolCfg.color}`}>
                      {toolCfg.label}
                    </span>
                    <span className="font-mono text-xs text-zinc-500">{file.size}</span>
                    <span className="text-xs text-zinc-500">{file.date}</span>
                    <span className={`text-xs ${file.isExpired ? "text-red-400" : "text-zinc-600"}`}>
                      {file.expires}
                    </span>
                    <div>
                      {file.canDownload && (
                        <a
                          href={`/tools/download/${file.uuid}`}
                          className="rounded-lg p-1 text-zinc-600 transition-colors hover:bg-zinc-700 hover:text-white inline-flex"
                        >
                          <Download className="h-4 w-4" />
                        </a>
                      )}
                      {file.awaitingPayment && (
                        <span className="text-xs text-amber-400 font-medium">Pending</span>
                      )}
                    </div>
                  </div>
                );
              })
            ) : (
              <div className="px-6 py-12 text-center text-zinc-500">No recent files yet.</div>
            )}
          </DocumateCard>
        </div>

        {/* Upgrade CTA — only shown when free tier and no credits */}
        {!hasActiveSubscription && creditBalance === 0 && (
          <DocumateCard className="mt-8 border-zinc-700">
            <div className="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
              <div className="flex items-start gap-4">
                <div className="rounded-xl bg-amber-500/10 p-2">
                  <Zap className="h-5 w-5 text-amber-400" />
                </div>
                <div>
                  <h4 className="font-semibold text-white">Need more PDF operations?</h4>
                  <p className="mt-1 text-sm text-zinc-500">
                    Subscribe for unlimited access or buy credits and pay only for what you use.
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-2 shrink-0">
                <Link href="/pricing">
                  <DocumateButton variant="outline" size="sm">Buy credits</DocumateButton>
                </Link>
                <Link href="/dashboard/billing">
                  <DocumateButton size="sm">Upgrade to Pro</DocumateButton>
                </Link>
              </div>
            </div>
          </DocumateCard>
        )}

      </div>
    </AppLayout>
  );
}
