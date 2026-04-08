"use client"

import { useState } from "react"
import { Check, ExternalLink, Crown } from "lucide-react"
import { AppLayout } from "@/components/documate/app-layout"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { BillingToggle, ProPlanCard, FREE_FEATURES } from "@/components/documate/plan-card"
import { router, usePage } from "@inertiajs/react"

type BillingPageProps = {
  subscription?: {
    name?: string
    stripe_status?: string
    amount?: number
    currency?: string
    stripe_price?: string
    trial_ends_at?: string | null
    ends_at?: string | null
    canceled?: boolean
    active_plan?: string
    label?: string
  }
  invoices?: Array<{
    id: string
    date: string
    total: number
    currency: string
    status: string
    hosted_invoice_url?: string
  }>
  plans?: Array<{
    id: string
    name: string
    price_monthly: number
    price_yearly: number
    features: string[]
    popular?: boolean
  }>
}

const PRO_YEARLY_PRICE_ID = "price_1THNL3PrtZkTUd5yZskTHj34"

const PRO_FEATURES = [
  "Unlimited PDF operations per day",
  "Up to 100MB per file",
  "All 7 tools — Merge, Split, Compress, Convert & more",
  "30-day file history",
  "Priority support",
]

export default function BillingPage() {
  const { subscription, invoices = [], plans = [] } = usePage().props as BillingPageProps
  const { flash = {} } = usePage().props as any

  const [billing, setBilling]     = useState<"monthly" | "yearly">("monthly")
  const [busy, setBusy]           = useState(false)
  const [upgrading, setUpgrading] = useState(false)

  const proPlan       = plans.find((p) => p.id === "pro")
  const monthlyPrice  = proPlan?.price_monthly ?? 9
  const yearlyPrice   = proPlan?.price_yearly  ?? 84

  const isPaidUser = Boolean(
    subscription &&
    !subscription.canceled &&
    (subscription.stripe_status === "active" || subscription.stripe_status === "trialing")
  )

  const isYearly  = subscription?.stripe_price === PRO_YEARLY_PRICE_ID
  const planLabel = subscription?.label ?? subscription?.active_plan ?? "Pro"

  const handleUpgrade = () => {
    setUpgrading(true)
    router.post(route("subscription.checkout", { plan: billing === "yearly" ? "pro_yearly" : "pro_monthly" }), {}, {
      onFinish: () => setUpgrading(false),
    })
  }

  const handleCancel = () => {
    setBusy(true)
    router.post(route("subscription.cancel"), {}, {
      onFinish: () => setBusy(false),
    })
  }

  return (
    <AppLayout>
      <div className="px-8 py-10">
        <h2 className="text-2xl font-semibold text-white">Billing</h2>
        <p className="mt-1 text-sm text-zinc-500">Manage your subscription and payment details.</p>

        {flash.info && (
          <div className="mt-4 rounded-xl border border-amber-700/50 bg-amber-950/30 px-4 py-3 text-sm text-amber-300">
            {flash.info}
          </div>
        )}
        {flash.success && (
          <div className="mt-4 rounded-xl border border-emerald-700/50 bg-emerald-950/30 px-4 py-3 text-sm text-emerald-400">
            {flash.success}
          </div>
        )}
        {flash.error && (
          <div className="mt-4 rounded-xl border border-red-700/50 bg-red-950/30 px-4 py-3 text-sm text-red-400">
            {flash.error}
          </div>
        )}

        {isPaidUser ? (
          /* ── PAID USER ── */
          <div className="mt-8 space-y-4">
            <DocumateCard>
              <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div className="flex items-start gap-4">
                  <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10">
                    <Crown className="h-5 w-5 text-amber-400" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-base font-semibold text-white">{planLabel}</span>
                      <DocumateBadge variant="success">Active</DocumateBadge>
                      {isYearly && <DocumateBadge variant="pro">Yearly</DocumateBadge>}
                    </div>
                    <p className="mt-0.5 text-sm text-zinc-400">
                      {subscription?.amount
                        ? `€${(subscription.amount / 100).toFixed(2)} billed ${isYearly ? "yearly" : "monthly"}`
                        : isYearly ? `€${yearlyPrice}/year` : `€${monthlyPrice}/month`}
                    </p>
                    {subscription?.canceled && subscription.ends_at && (
                      <p className="mt-1 text-xs text-amber-400">
                        Access until {new Date(subscription.ends_at).toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" })}
                      </p>
                    )}
                    {subscription?.trial_ends_at && (
                      <p className="mt-1 text-xs text-blue-400">
                        Trial ends {new Date(subscription.trial_ends_at).toLocaleDateString()}
                      </p>
                    )}
                  </div>
                </div>
                {!subscription?.canceled && (
                  <button onClick={handleCancel} disabled={busy}
                    className="text-sm text-zinc-500 transition-colors hover:text-red-400 disabled:opacity-40">
                    {busy ? "Canceling..." : "Cancel subscription"}
                  </button>
                )}
              </div>

              <div className="mt-6 grid grid-cols-1 gap-2 border-t border-zinc-800 pt-5 sm:grid-cols-2">
                {PRO_FEATURES.map((f) => (
                  <div key={f} className="flex items-center gap-2 text-sm text-zinc-400">
                    <Check className="h-4 w-4 shrink-0 text-emerald-400" />
                    {f}
                  </div>
                ))}
              </div>
            </DocumateCard>

            <DocumateCard padding="none" className="overflow-hidden">
              <div className="border-b border-zinc-800 px-6 py-4">
                <span className="font-semibold text-white">Invoices</span>
              </div>
              {invoices.length === 0 ? (
                <div className="px-6 py-8 text-center text-sm text-zinc-500">No invoices yet.</div>
              ) : (
                invoices.map((inv) => (
                  <div key={inv.id} className="flex items-center justify-between border-b border-zinc-800 px-6 py-4 last:border-0">
                    <div>
                      <span className="font-mono text-xs text-zinc-300">{inv.id}</span>
                      <p className="text-xs text-zinc-500">{inv.date}</p>
                    </div>
                    <div className="flex items-center gap-4">
                      <span className="font-mono text-sm text-white">€{(inv.total / 100).toFixed(2)}</span>
                      <DocumateBadge variant={inv.status === "paid" ? "success" : "default"}>{inv.status}</DocumateBadge>
                      {inv.hosted_invoice_url ? (
                        <a href={inv.hosted_invoice_url} target="_blank" rel="noopener noreferrer"
                          className="rounded-lg p-1.5 text-zinc-500 transition-colors hover:bg-zinc-800 hover:text-white">
                          <ExternalLink className="h-3.5 w-3.5" />
                        </a>
                      ) : <div className="w-7" />}
                    </div>
                  </div>
                ))
              )}
            </DocumateCard>
          </div>
        ) : (
          /* ── FREE USER ── */
          <div className="mt-8 flex flex-col items-center">
            <BillingToggle value={billing} onChange={setBilling} savingsPct={Math.round((monthlyPrice * 12 - yearlyPrice) / (monthlyPrice * 12) * 100)} />

            <div className="mt-6 w-full max-w-sm">
              <ProPlanCard
                billing={billing}
                monthlyPrice={monthlyPrice}
                yearlyPrice={yearlyPrice}
                onUpgrade={handleUpgrade}
                loading={upgrading}
              />

              <div className="mt-4 rounded-xl border border-zinc-800 bg-zinc-900/50 px-5 py-4">
                <p className="text-xs font-medium uppercase tracking-wide text-zinc-500">Your current plan — Free</p>
                <ul className="mt-3 space-y-2">
                  {FREE_FEATURES.map((f) => (
                    <li key={f} className="flex items-center gap-2 text-sm text-zinc-500">
                      <div className="h-1 w-1 rounded-full bg-zinc-600" />
                      {f}
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  )
}
