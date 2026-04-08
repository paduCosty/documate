"use client"

import { useForm, Link } from "@inertiajs/react"
import { MailCheck } from "lucide-react"
import { GuestLayout } from "@/components/documate/guest-layout"
import { DocumateButton } from "@/components/documate/documate-button"

interface Props {
  status?: string
}

export default function VerifyEmailPage({ status }: Props) {
  const { post, processing } = useForm({})

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    post(route("verification.send"))
  }

  return (
    <GuestLayout>
      <div className="w-full max-w-[400px] rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-[0_0_0_1px_#3f3f46,0_16px_48px_rgba(0,0,0,0.6)] text-center">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-zinc-800">
          <MailCheck className="h-8 w-8 text-zinc-300" />
        </div>

        <h2 className="mt-6 text-xl font-semibold text-white">Verify your email</h2>
        <p className="mt-2 text-sm text-zinc-400">
          Thanks for signing up! Please click the verification link we sent to your email address to get started.
        </p>

        {status === "verification-link-sent" && (
          <div className="mt-4 rounded-lg bg-emerald-950/40 border border-emerald-800/50 px-4 py-3 text-sm text-emerald-400">
            A new verification link has been sent to your email address.
          </div>
        )}

        <form onSubmit={submit} className="mt-6">
          <DocumateButton type="submit" className="w-full" disabled={processing}>
            {processing ? "Sending…" : "Resend verification email"}
          </DocumateButton>
        </form>

        <Link
          href={route("logout")}
          method="post"
          as="button"
          className="mt-4 block text-sm text-zinc-600 transition-colors hover:text-zinc-400"
        >
          Sign out
        </Link>
      </div>
    </GuestLayout>
  )
}
