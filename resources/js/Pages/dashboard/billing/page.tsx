"use client"

import { useState, useEffect } from "react"
import { CreditCard, Download, Zap, ShieldCheck } from "lucide-react"
import { AppLayout } from "@/components/documate/app-layout"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateButton } from "@/components/documate/documate-button"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog"
import { router, usePage } from "@inertiajs/react"

type BillingPageProps = {
  subscription?: {
    name?: string
    stripe_status?: string
    amount?: string | number
    currency?: string
    stripe_price?: string
    trial_ends_at?: string | null
    ends_at?: string | null
    canceled?: boolean
  }
  invoices?: Array<{ id: string; date: string; total: number; currency: string; status: string; hosted_invoice_url?: string }>
  plans?: Array<{
    id: string
    name: string
    price_monthly: number
    price_yearly: number
    features: string[]
    popular?: boolean
  }>
  flash?: { success?: string; error?: string }
}

export default function BillingPage() {
  const { subscription, invoices = [], plans = [], flash = {} } = usePage().props as BillingPageProps
  const [busy, setBusy] = useState(false)
  const [upgrading, setUpgrading] = useState(false)

  const activePlan = subscription?.active_plan ?? (subscription?.name ? subscription.name.replace("_", " ") : "Free")
  const isPaidUser = Boolean(subscription && !subscription.canceled && (subscription.stripe_status === "active" || subscription.stripe_status === "trialing" || subscription.status === "active" ))
  const planName = activePlan

  const proPlan = plans.find(plan => plan.id === 'pro')

  const handleCancelSubscription = () => {
    if (!confirm("Are you sure you want to cancel your subscription?")) {
      return
    }

    setBusy(true)
    router.post(route("subscription.cancel"), {}, {
      onFinish: () => setBusy(false),
    })
  }

  return (
    <AppLayout user={{ name: "Alex Johnson", email: "alex@example.com", plan: planName.toLowerCase() }}>
      <div className="px-8 py-10">
        <h2 className="text-2xl font-semibold text-white">Billing</h2>

        {flash.success && <p className="rounded-md bg-emerald-500/20 p-2 text-sm text-emerald-300">{flash.success}</p>}
        {flash.error && <p className="rounded-md bg-rose-500/20 p-2 text-sm text-rose-300">{flash.error}</p>}

        {isPaidUser ? (
          <>
            <DocumateCard className="mt-6">
              <div className="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                  <div className="flex items-center gap-2">
                    <DocumateBadge variant="pro">{planName}</DocumateBadge>
                    <span className="text-lg font-semibold text-white">{planName} Plan</span>
                  </div>
                  <p className="mt-1 text-sm text-zinc-500">Status: {subscription?.canceled ? "Canceled" : (subscription?.stripe_status ?? "active")}</p>
                  {subscription?.canceled && subscription?.ends_at && (
                    <p className="mt-1 text-sm text-zinc-400">Access until: {new Date(subscription.ends_at).toLocaleDateString()}</p>
                  )}
                  <p className="mt-1 text-sm text-zinc-400">Plan: {planName}</p>
                  <p className="mt-1 text-sm text-zinc-400">
                    {subscription?.amount ? `${(Number(subscription.amount) / 100).toFixed(2)} ${subscription.currency || "EUR"}` : "€7.00 / month"}
                  </p>
                </div>
                <div className="flex flex-col items-end gap-2">
                  <DocumateButton variant="outline" size="sm" disabled={!subscription || busy}>Change plan</DocumateButton>
                  <button onClick={handleCancelSubscription} className="text-sm text-zinc-600 transition-colors hover:text-red-400" disabled={busy || subscription?.canceled}>
                    {busy ? "Canceling..." : subscription?.canceled ? "Canceled" : "Cancel subscription"}
                  </button>
                </div>
              </div>
            </DocumateCard>

            <DocumateCard className="mt-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <CreditCard className="h-5 w-5 text-zinc-500" />
                  <div>
                    <span className="text-sm font-medium text-white">Card saved</span>
                    <p className="text-xs text-zinc-500">Visa ending in 4242 (test)</p>
                  </div>
                </div>
                <DocumateButton variant="ghost" size="sm">Update</DocumateButton>
              </div>
            </DocumateCard>

            <DocumateCard className="mt-4 overflow-hidden p-0">
              <div className="border-b border-zinc-800 px-6 py-4">
                <span className="font-semibold text-white">Invoices</span>
              </div>
              <div>
                {invoices.length === 0 ? (
                  <div className="px-6 py-4 text-sm text-zinc-400">No invoices yet.</div>
                ) : (
                  invoices.map((invoice) => (
                    <div key={invoice.id} className="flex items-center justify-between border-b border-zinc-800 px-6 py-4 last:border-0">
                      <span className="font-mono text-xs text-zinc-300">{invoice.id}</span>
                      <span className="text-xs text-zinc-500">{invoice.date}</span>
                      <span className="font-mono text-sm text-white">{(invoice.total / 100).toFixed(2)} {invoice.currency?.toUpperCase() ?? "EUR"}</span>
                      <DocumateBadge variant={invoice.status === "paid" ? "success" : "default"}>{invoice.status}</DocumateBadge>
                      <button className="rounded-lg p-1 text-zinc-500 transition-colors hover:bg-zinc-800 hover:text-white" disabled>
                        View
                      </button>
                    </div>
                  ))
                )}
              </div>
            </DocumateCard>
          </>
        ) : (
          <DocumateCard className="mt-6 border-zinc-700 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-500/10">
              <Zap className="h-6 w-6 text-amber-400" />
            </div>
            <h3 className="mt-4 text-xl font-semibold text-white">Upgrade to {proPlan?.name || 'Pro'}</h3>
            <ul className="mt-4 space-y-2 text-sm text-zinc-400">
              {proPlan?.features.map((feature, index) => (
                <li key={index}>{feature}</li>
              ))}
            </ul>
            <DocumateButton size="lg" className="mt-6" disabled={upgrading} onClick={() => { setUpgrading(true); router.post(route("subscription.checkout", { plan: "pro_monthly" })) }}>
              {upgrading ? "Upgrading..." : `Upgrade — €${proPlan?.price_monthly}/month`}
            </DocumateButton>
            <div className="mt-3 flex items-center justify-center gap-1 text-xs text-zinc-600">
              <ShieldCheck className="h-3.5 w-3.5" />
              30-day money-back guarantee
            </div>
          </DocumateCard>
        )}
      </div>
    </AppLayout>
  )
}
