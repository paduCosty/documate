"use client";

import { ArrowLeft, Check, Shield, Lock } from "lucide-react";
import { Link, router } from "@inertiajs/react";
import { GuestLayout } from "@/components/documate/guest-layout";
import { Logo } from "@/components/documate/logo";

interface Product {
  id: string;
  name: string;
  priceInCents: number;
  interval: 'month' | 'year';
  features: string[];
  description?: string;
}

interface Props {
  product: Product;
}

export default function CheckoutPage({ product }: Props) {
  
  const formatPrice = (cents: number) => {
    return new Intl.NumberFormat('ro-RO', {
      style: 'currency',
      currency: 'EUR',
    }).format(cents / 100);
  };

  const handleCheckout = () => {
    router.post(`/subscription/checkout/${product.id}`);
  };

  return (
    <GuestLayout>
      <div className="min-h-screen bg-zinc-950">
        {/* Header */}
        <header className="border-b border-zinc-800 py-5">
          <div className="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <Link href="/">
              <Logo />
            </Link>
            <Link 
              href="/pricing"
              className="flex items-center gap-2 text-sm text-zinc-400 hover:text-white transition-colors"
            >
              <ArrowLeft className="h-4 w-4" />
              Back to pricing
            </Link>
          </div>
        </header>

        <div className="max-w-6xl mx-auto px-6 py-12">
          <div className="grid lg:grid-cols-2 gap-12">
            
            {/* Order Summary */}
            <div className="lg:order-2">
              <div className="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 sticky top-8">
                <h2 className="text-xl font-semibold text-white mb-6">Order Summary</h2>

                <div className="space-y-6">
                  <div className="flex justify-between">
                    <div>
                      <h3 className="font-medium text-white text-lg">{product.name}</h3>
                      <p className="text-zinc-400 text-sm mt-1">
                        {product.interval === 'year' ? 'Annual billing' : 'Monthly billing'}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-2xl font-semibold text-white">
                        {formatPrice(product.priceInCents)}
                      </p>
                      <p className="text-sm text-zinc-500">
                        /{product.interval === 'year' ? 'year' : 'month'}
                      </p>
                    </div>
                  </div>

                  <div className="border-t border-zinc-800 pt-6">
                    <h4 className="text-sm font-medium text-white mb-4">What's included:</h4>
                    <ul className="space-y-3">
                      {product.features.map((feature, index) => (
                        <li key={index} className="flex items-start gap-3">
                          <Check className="h-5 w-5 text-emerald-400 mt-0.5 shrink-0" />
                          <span className="text-zinc-300">{feature}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                </div>

                {/* Trust signals */}
                <div className="border-t border-zinc-800 mt-8 pt-6 flex items-center gap-6 text-xs text-zinc-500">
                  <div className="flex items-center gap-1.5">
                    <Shield className="h-4 w-4" />
                    Secure checkout
                  </div>
                  <div className="flex items-center gap-1.5">
                    <Lock className="h-4 w-4" />
                    SSL encrypted
                  </div>
                </div>
              </div>
            </div>

            {/* Checkout Area */}
            <div className="lg:order-1">
              <div className="mb-10">
                <h1 className="text-3xl font-semibold text-white mb-3">Complete your purchase</h1>
                <p className="text-zinc-400">
                  Subscribe to <span className="text-white font-medium">{product.name}</span>
                </p>
              </div>

              <button
                onClick={handleCheckout}
                className="w-full bg-white hover:bg-zinc-100 transition-colors text-black font-semibold py-4 rounded-2xl text-lg shadow-button"
              >
                Pay securely with Stripe — {formatPrice(product.priceInCents)}
              </button>

              <div className="mt-8 text-center text-xs text-zinc-500 space-y-1">
                <p>Powered by Stripe • Secure payment</p>
                <p>You can cancel your subscription anytime</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </GuestLayout>
  );
}