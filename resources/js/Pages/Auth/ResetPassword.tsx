"use client"

import { useState } from "react"
import { useForm } from "@inertiajs/react"
import { Eye, EyeOff } from "lucide-react"
import { GuestLayout } from "@/components/documate/guest-layout"
import { DocumateButton } from "@/components/documate/documate-button"
import { DocumateInput } from "@/components/documate/documate-input"

interface Props {
  token: string
  email: string
}

export default function ResetPasswordPage({ token, email }: Props) {
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirm, setShowConfirm]   = useState(false)

  const { data, setData, post, processing, errors, reset } = useForm({
    token,
    email,
    password: "",
    password_confirmation: "",
  })

  const passwordStrength = (() => {
    let s = 0
    if (data.password.length >= 8) s++
    if (/[A-Z]/.test(data.password)) s++
    if (/[0-9]/.test(data.password)) s++
    if (/[^A-Za-z0-9]/.test(data.password)) s++
    return s
  })()

  const strengthColors = ["bg-red-500", "bg-orange-500", "bg-yellow-500", "bg-green-500"]

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post(route("password.store"), { onFinish: () => reset("password", "password_confirmation") })
  }

  return (
    <GuestLayout>
      <div className="w-full max-w-[400px] rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-[0_0_0_1px_#3f3f46,0_16px_48px_rgba(0,0,0,0.6)]">
        <h2 className="text-xl font-semibold text-white">Set new password</h2>
        <p className="mt-1 text-sm text-zinc-500">Create a strong password for your account.</p>

        <form onSubmit={handleSubmit} className="mt-8 space-y-4">
          {/* Hidden email field for accessibility / autofill */}
          <input type="hidden" name="email" value={data.email} />

          <div>
            <div className="relative">
              <DocumateInput
                label="New password"
                type={showPassword ? "text" : "password"}
                placeholder="Create a new password"
                value={data.password}
                onChange={(e) => setData("password", e.target.value)}
                required
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-[30px] text-zinc-600 transition-colors hover:text-zinc-400"
              >
                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              </button>
            </div>
            {data.password && (
              <div className="mt-2 flex gap-1">
                {[0, 1, 2, 3].map((i) => (
                  <div
                    key={i}
                    className={`h-1 flex-1 rounded-full ${
                      i < passwordStrength ? strengthColors[passwordStrength - 1] : "bg-zinc-800"
                    }`}
                  />
                ))}
              </div>
            )}
            {errors.password && <p className="mt-1 text-xs text-red-400">{errors.password}</p>}
          </div>

          <div className="relative">
            <DocumateInput
              label="Confirm password"
              type={showConfirm ? "text" : "password"}
              placeholder="Confirm your new password"
              value={data.password_confirmation}
              onChange={(e) => setData("password_confirmation", e.target.value)}
              required
            />
            <button
              type="button"
              onClick={() => setShowConfirm(!showConfirm)}
              className="absolute right-3 top-[30px] text-zinc-600 transition-colors hover:text-zinc-400"
            >
              {showConfirm ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
            {errors.password_confirmation && (
              <p className="mt-1 text-xs text-red-400">{errors.password_confirmation}</p>
            )}
          </div>

          <DocumateButton type="submit" className="w-full" disabled={processing}>
            {processing ? "Resetting…" : "Reset password"}
          </DocumateButton>
        </form>
      </div>
    </GuestLayout>
  )
}
