"use client"

import { useForm } from "@inertiajs/react"
import { CheckCircle2 } from "lucide-react"
import { Link } from "@inertiajs/react"
import { GuestLayout } from "@/components/documate/guest-layout"
import { DocumateButton } from "@/components/documate/documate-button"
import { DocumateInput } from "@/components/documate/documate-input"

interface Props {
  status?: string
}

export default function ForgotPasswordPage({ status }: Props) {
  const { data, setData, post, processing, errors } = useForm({ email: "" })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post(route("password.email"))
  }

  return (
    <GuestLayout>
      <div className="w-full max-w-[400px] rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-[0_0_0_1px_#3f3f46,0_16px_48px_rgba(0,0,0,0.6)]">
        {status ? (
          <div className="text-center">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[rgba(34,197,94,0.10)]">
              <CheckCircle2 className="h-8 w-8 text-[#22c55e]" />
            </div>
            <h2 className="mt-6 text-xl font-semibold text-white">Check your email</h2>
            <p className="mt-2 text-sm text-zinc-400">{status}</p>
            <Link href="/login" className="mt-6 block text-center text-sm text-zinc-500 transition-colors hover:text-white">
              &larr; Back to sign in
            </Link>
          </div>
        ) : (
          <>
            <h2 className="text-xl font-semibold text-white">Reset your password</h2>
            <p className="mt-1 text-sm text-zinc-500">Enter your email and we&apos;ll send you a reset link.</p>

            <form onSubmit={handleSubmit} className="mt-8 space-y-4">
              <div>
                <DocumateInput
                  label="Email"
                  type="email"
                  placeholder="you@example.com"
                  value={data.email}
                  onChange={(e) => setData("email", e.target.value)}
                  required
                />
                {errors.email && (
                  <p className="mt-1 text-xs text-red-400">{errors.email}</p>
                )}
              </div>
              <DocumateButton type="submit" className="w-full" disabled={processing}>
                {processing ? "Sending…" : "Send reset link"}
              </DocumateButton>
            </form>

            <Link href="/login" className="mt-6 block text-center text-sm text-zinc-500 transition-colors hover:text-white">
              &larr; Back to sign in
            </Link>
          </>
        )}
      </div>
    </GuestLayout>
  )
}
