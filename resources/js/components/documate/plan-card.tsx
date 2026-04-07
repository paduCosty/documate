import { Check, Crown, ShieldCheck } from "lucide-react"
import { DocumateBadge } from "@/components/documate/documate-badge"

export const PRO_FEATURES = [
  "Unlimited PDF operations per day",
  "Up to 100MB per file",
  "All 7 tools — Merge, Split, Compress, Convert & more",
  "30-day file history",
  "Priority support",
]

export const FREE_FEATURES = [
  "3 operations/day",
  "10MB max file size",
  "Merge & Compress PDF",
  "Word/Excel/PPT to PDF",
]

type BillingCycle = "monthly" | "yearly"

interface BillingToggleProps {
  value: BillingCycle
  onChange: (v: BillingCycle) => void
  savingsPct?: number
}

export function BillingToggle({ value, onChange, savingsPct = 22 }: BillingToggleProps) {
  return (
    <div className="flex items-center rounded-xl border border-zinc-800 bg-zinc-900 p-1">
      <button
        onClick={() => onChange("monthly")}
        className={`rounded-lg px-5 py-2 text-sm font-medium transition-all duration-200 ${
          value === "monthly" ? "bg-zinc-700 text-white shadow-sm" : "text-zinc-500 hover:text-white"
        }`}>
        Monthly
      </button>
      <button
        onClick={() => onChange("yearly")}
        className={`flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-medium transition-all duration-200 ${
          value === "yearly" ? "bg-zinc-700 text-white shadow-sm" : "text-zinc-500 hover:text-white"
        }`}>
        Yearly
        <span className="rounded-full bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-400">
          -{savingsPct}%
        </span>
      </button>
    </div>
  )
}

interface ProPlanCardProps {
  billing: BillingCycle
  monthlyPrice: number
  yearlyPrice: number
  onUpgrade: () => void
  loading?: boolean
  /** Show as standalone (pricing page) or embedded (billing page) */
  variant?: "page" | "embedded"
}

export function ProPlanCard({
  billing,
  monthlyPrice,
  yearlyPrice,
  onUpgrade,
  loading = false,
  variant = "embedded",
}: ProPlanCardProps) {
  const perMonth = Math.round(yearlyPrice / 12)
  const savings  = monthlyPrice * 12 - yearlyPrice

  return (
    <div className={`overflow-hidden rounded-2xl border bg-zinc-900 ${variant === "page" ? "border-white/20 shadow-[0_0_60px_rgba(255,255,255,0.04)]" : "border-zinc-700"}`}>
      {/* Header */}
      <div className="border-b border-zinc-800 bg-zinc-800/40 px-6 py-5">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Crown className="h-4 w-4 text-amber-400" />
            <span className="font-semibold text-white">Pro</span>
          </div>
          <DocumateBadge variant="pro">Most popular</DocumateBadge>
        </div>

        <div className="mt-4 flex items-end gap-1">
          {billing === "monthly" ? (
            <>
              <span className="text-4xl font-bold text-white">€{monthlyPrice}</span>
              <span className="mb-1 text-sm text-zinc-500">/month</span>
            </>
          ) : (
            <>
              <span className="text-4xl font-bold text-white">€{perMonth}</span>
              <span className="mb-1 text-sm text-zinc-500">/month</span>
              <span className="mb-1 ml-2 text-xs text-zinc-500">· €{yearlyPrice} billed yearly</span>
            </>
          )}
        </div>

        {billing === "yearly" ? (
          <p className="mt-1 text-xs text-emerald-400">You save €{savings} compared to monthly</p>
        ) : (
          <p className="mt-1 text-xs text-zinc-600">or €{perMonth}/mo billed yearly</p>
        )}
      </div>

      {/* Features */}
      <div className="px-6 py-5">
        <ul className="space-y-3">
          {PRO_FEATURES.map((f) => (
            <li key={f} className="flex items-center gap-3 text-sm text-zinc-300">
              <Check className="h-4 w-4 shrink-0 text-emerald-400" />
              {f}
            </li>
          ))}
        </ul>
      </div>

      {/* CTA */}
      <div className="border-t border-zinc-800 px-6 pb-6 pt-5">
        <button
          onClick={onUpgrade}
          disabled={loading}
          className="w-full rounded-xl bg-white py-3 text-sm font-semibold text-zinc-900 transition-colors hover:bg-zinc-100 disabled:opacity-50">
          {loading
            ? "Redirecting to checkout..."
            : billing === "yearly"
              ? `Get Pro — €${yearlyPrice}/year`
              : `Get Pro — €${monthlyPrice}/month`}
        </button>
        <div className="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-600">
          <ShieldCheck className="h-3.5 w-3.5" />
          30-day money-back guarantee · Cancel anytime
        </div>
      </div>
    </div>
  )
}
