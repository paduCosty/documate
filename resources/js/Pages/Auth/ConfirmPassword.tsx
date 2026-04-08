"use client"

import { useForm } from "@inertiajs/react"
import { ShieldCheck } from "lucide-react"
import { GuestLayout } from "@/components/documate/guest-layout"
import { DocumateButton } from "@/components/documate/documate-button"
import { DocumateInput } from "@/components/documate/documate-input"

export default function ConfirmPasswordPage() {
  const { data, setData, post, processing, errors, reset } = useForm({ password: "" })

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    post(route("password.confirm"), { onFinish: () => reset("password") })
  }

  return (
    <GuestLayout>
      <div className="w-full max-w-[400px] rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-[0_0_0_1px_#3f3f46,0_16px_48px_rgba(0,0,0,0.6)]">
        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-800">
          <ShieldCheck className="h-6 w-6 text-zinc-300" />
        </div>

        <h2 className="mt-4 text-xl font-semibold text-white">Confirm your password</h2>
        <p className="mt-1 text-sm text-zinc-500">
          This is a secure area. Please confirm your password before continuing.
        </p>

        <form onSubmit={submit} className="mt-6 space-y-4">
          <div>
            <DocumateInput
              label="Password"
              type="password"
              placeholder="Enter your password"
              value={data.password}
              onChange={(e) => setData("password", e.target.value)}
              required
              autoFocus
            />
            {errors.password && (
              <p className="mt-1 text-xs text-red-400">{errors.password}</p>
            )}
          </div>

          <DocumateButton type="submit" className="w-full" disabled={processing}>
            {processing ? "Confirming…" : "Confirm password"}
          </DocumateButton>
        </form>
      </div>
    </GuestLayout>
  )
}
