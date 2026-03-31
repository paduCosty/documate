"use client"

import { useState } from "react"
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

const invoices = [
  { id: "INV-2026-003", date: "Mar 1, 2026", amount: "€7.00", status: "Paid" },
  { id: "INV-2026-002", date: "Feb 1, 2026", amount: "€7.00", status: "Paid" },
  { id: "INV-2026-001", date: "Jan 1, 2026", amount: "€7.00", status: "Paid" },
  { id: "INV-2025-012", date: "Dec 1, 2025", amount: "€7.00", status: "Paid" },
  { id: "INV-2025-011", date: "Nov 1, 2025", amount: "€7.00", status: "Paid" },
]

// Set this to true to show the paid user view, false for free user view
const isPaidUser = true

export default function BillingPage() {
  return (
    <AppLayout user={{ name: "Alex Johnson", email: "alex@example.com", plan: isPaidUser ? "pro" : "free" }}>
      <div className="px-8 py-10">
        <h2 className="text-2xl font-semibold text-white">Billing</h2>

        {isPaidUser ? (
          <>
            {/* Current Plan Card (Paid User) */}
            <DocumateCard className="mt-6">
              <div className="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                  <div className="flex items-center gap-2">
                    <DocumateBadge variant="pro">Pro</DocumateBadge>
                    <span className="text-lg font-semibold text-white">Pro Plan</span>
                  </div>
                  <p className="mt-1 text-sm text-zinc-500">Renews on March 15, 2026</p>
                  <p className="mt-1 text-sm text-zinc-400">&euro;7.00 / month</p>
                </div>
                <div className="flex flex-col items-end gap-2">
                  <DocumateButton variant="outline" size="sm">Change plan</DocumateButton>
                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <button className="text-sm text-zinc-600 transition-colors hover:text-red-400">
                        Cancel subscription
                      </button>
                    </AlertDialogTrigger>
                    <AlertDialogContent className="border-zinc-800 bg-zinc-900">
                      <AlertDialogHeader>
                        <AlertDialogTitle className="text-white">Cancel your subscription?</AlertDialogTitle>
                        <AlertDialogDescription className="text-zinc-400">
                          If you cancel, you&apos;ll lose access to:
                          <ul className="mt-2 list-inside list-disc space-y-1">
                            <li>Unlimited operations</li>
                            <li>100MB file uploads</li>
                            <li>Sign PDF and OCR features</li>
                            <li>30-day file history</li>
                          </ul>
                          <p className="mt-3">Your subscription will remain active until March 15, 2026.</p>
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter className="mt-4">
                        <AlertDialogCancel className="border-zinc-700 bg-transparent text-zinc-400 hover:bg-zinc-800 hover:text-white">
                          Keep my plan
                        </AlertDialogCancel>
                        <AlertDialogAction className="bg-zinc-800 text-zinc-400 hover:bg-zinc-700">
                          Cancel subscription
                        </AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>
                </div>
              </div>
            </DocumateCard>

            {/* Payment Method Card */}
            <DocumateCard className="mt-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <CreditCard className="h-5 w-5 text-zinc-500" />
                  <div>
                    <span className="text-sm font-medium text-white">Visa ending in 4242</span>
                    <p className="text-xs text-zinc-500">Expires 12/26</p>
                  </div>
                </div>
                <DocumateButton variant="ghost" size="sm">Update</DocumateButton>
              </div>
            </DocumateCard>

            {/* Invoices Card */}
            <DocumateCard className="mt-4 overflow-hidden p-0">
              <div className="border-b border-zinc-800 px-6 py-4">
                <span className="font-semibold text-white">Invoices</span>
              </div>
              <div>
                {invoices.map((invoice) => (
                  <div
                    key={invoice.id}
                    className="flex items-center justify-between border-b border-zinc-800 px-6 py-4 last:border-0"
                  >
                    <span className="font-mono text-xs text-zinc-300">{invoice.id}</span>
                    <span className="text-xs text-zinc-500">{invoice.date}</span>
                    <span className="font-mono text-sm text-white">{invoice.amount}</span>
                    <DocumateBadge variant="success">{invoice.status}</DocumateBadge>
                    <button className="rounded-lg p-1 text-zinc-500 transition-colors hover:bg-zinc-800 hover:text-white">
                      <Download className="h-4 w-4" />
                    </button>
                  </div>
                ))}
              </div>
            </DocumateCard>
          </>
        ) : (
          /* Upgrade Card (Free User) */
          <DocumateCard className="mt-6 border-zinc-700 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-500/10">
              <Zap className="h-6 w-6 text-amber-400" />
            </div>
            <h3 className="mt-4 text-xl font-semibold text-white">Upgrade to Pro</h3>
            <ul className="mt-4 space-y-2 text-sm text-zinc-400">
              <li>Unlimited operations</li>
              <li>100MB max file size</li>
              <li>Sign PDF + OCR</li>
            </ul>
            <DocumateButton size="lg" className="mt-6">Upgrade — &euro;7/month</DocumateButton>
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
