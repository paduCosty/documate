"use client"

import { useState } from "react"
import { Eye, EyeOff } from "lucide-react"
import { GuestLayout } from "@/components/documate/guest-layout"
import { DocumateButton } from "@/components/documate/documate-button"
import { DocumateInput } from "@/components/documate/documate-input"

export default function ResetPasswordPage() {
  const [showPassword, setShowPassword] = useState(false)
  const [passwordStrength, setPasswordStrength] = useState(0)

  const handlePasswordChange = (value: string) => {
    let strength = 0
    if (value.length >= 8) strength++
    if (/[A-Z]/.test(value)) strength++
    if (/[0-9]/.test(value)) strength++
    if (/[^A-Za-z0-9]/.test(value)) strength++
    setPasswordStrength(strength)
  }

  const strengthColors = ["bg-red-500", "bg-orange-500", "bg-yellow-500", "bg-green-500"]

  return (
    <GuestLayout>
      <div className="w-full max-w-[400px] rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-[0_0_0_1px_#3f3f46,0_16px_48px_rgba(0,0,0,0.6)]">
        <h2 className="text-xl font-semibold text-white">Set new password</h2>
        <p className="mt-1 text-sm text-zinc-500">Create a strong password for your account.</p>

        <form className="mt-8 space-y-4">
          <div className="relative">
            <DocumateInput
              label="New password"
              type={showPassword ? "text" : "password"}
              placeholder="Create a new password"
              onChange={(e) => handlePasswordChange(e.target.value)}
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-[30px] text-zinc-600 transition-colors hover:text-zinc-400"
            >
              {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
            {/* Password Strength Bar */}
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
          </div>

          <DocumateInput label="Confirm password" type="password" placeholder="Confirm your new password" />

          <DocumateButton className="w-full">Reset password</DocumateButton>
        </form>
      </div>
    </GuestLayout>
  )
}
