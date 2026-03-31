"use client"

import { useState } from "react"
import { Link } from "@inertiajs/react"
import { CheckCircle2 } from "lucide-react"
import { GuestLayout } from "@/components/documate/guest-layout"
import { DocumateButton } from "@/components/documate/documate-button"
import { DocumateInput } from "@/components/documate/documate-input"

export default function ForgotPasswordPage() {
  const [isSubmitted, setIsSubmitted] = useState(false)
  const [email, setEmail] = useState("")

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setIsSubmitted(true)
  }

  return (
    <GuestLayout>
      <div className="w-full max-w-[400px] rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-[0_0_0_1px_#3f3f46,0_16px_48px_rgba(0,0,0,0.6)]">
        {!isSubmitted ? (
          <>
            <h2 className="text-xl font-semibold text-white">Reset your password</h2>
            <p className="mt-1 text-sm text-zinc-500">Enter your email and we&apos;ll send you a reset link.</p>

            <form onSubmit={handleSubmit} className="mt-8 space-y-4">
              <DocumateInput
                label="Email"
                type="email"
                placeholder="you@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
              <DocumateButton type="submit" className="w-full">Send reset link</DocumateButton>
            </form>

            <Link href="/login" className="mt-6 block text-center text-sm text-zinc-500 transition-colors hover:text-white">
              &larr; Back to sign in
            </Link>
          </>
        ) : (
          <div className="text-center">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[rgba(34,197,94,0.10)]">
              <CheckCircle2 className="h-8 w-8 text-[#22c55e]" />
            </div>
            <h2 className="mt-6 text-xl font-semibold text-white">Check your email</h2>
            <p className="mt-2 text-sm text-zinc-500">We sent a reset link to {email}</p>
            <button className="mt-4 text-xs text-zinc-600 transition-colors hover:text-white">
              Didn&apos;t receive it? Resend &rarr;
            </button>
          </div>
        )}
      </div>
    </GuestLayout>
  )
}
