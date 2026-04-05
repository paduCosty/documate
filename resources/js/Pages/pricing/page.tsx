"use client";

import { useState } from "react";
import { Link, router } from "@inertiajs/react";
import { Check, ShieldCheck } from "lucide-react";
import { Navbar } from "@/components/documate/navbar";
import { Footer } from "@/components/documate/footer";
import { DocumateCard } from "@/components/documate/documate-card";
import { DocumateBadge } from "@/components/documate/documate-badge";
import { DocumateButton } from "@/components/documate/documate-button";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";

interface Plan {
  id: string;
  name: string;
  price_monthly: number;
  price_yearly: number;
  features: string[];
  popular?: boolean;
}

interface Props {
  plans: Plan[];
}

// FAQ-urile (le punem direct în componentă)
const faqs = [
  { 
    question: "Can I cancel anytime?", 
    answer: "Yes, you can cancel your subscription at any time. Your access will continue until the end of your billing period." 
  },
  { 
    question: "What payment methods do you accept?", 
    answer: "We accept all major credit cards (Visa, MasterCard, American Express) and SEPA. All payments are processed securely through Stripe." 
  },
  { 
    question: "Is there a free trial?", 
    answer: "We don't offer a traditional free trial, but our Free plan gives you 3 operations per day forever. You can upgrade anytime." 
  },
  { 
    question: "Can I switch plans?", 
    answer: "Yes, you can upgrade or downgrade your plan at any time. Prorated charges apply when upgrading." 
  },
  { 
    question: "Do you offer refunds?", 
    answer: "Yes, we offer a 30-day money-back guarantee on all paid plans." 
  },
];

export default function PricingPage({ plans }: Props) {
  const [isYearly, setIsYearly] = useState(false);
console.log(plans);
  const getPlan = (planId: string) => {
    return plans.find((p) => p.id === planId);
  };

  const handleSelectPlan = (planId: string) => {
    router.visit(`/checkout/${planId}`);
  };

  const formatPrice = (price: number, isYearly: boolean) => {
    const amount = isYearly ? price / 12 : price;
    return `€${amount.toFixed(isYearly ? 2 : 0)}`;
  };

  const proPlan = getPlan("pro");
  const businessPlan = getPlan("business");

  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />

      <main className="px-6 py-24">
        <div className="mx-auto max-w-5xl text-center">
          <h1 className="text-4xl font-bold tracking-tight text-white md:text-5xl">
            Simple pricing
          </h1>
          <p className="mt-4 text-zinc-500">Start free. No credit card required.</p>

          {/* Toggle Monthly / Yearly */}
          <div className="mt-10 flex items-center justify-center gap-2">
            <div className="inline-flex rounded-full bg-zinc-800 p-1">
              <button
                onClick={() => setIsYearly(false)}
                className={`rounded-full px-6 py-2 text-sm font-medium transition-all ${
                  !isYearly ? "bg-zinc-600 text-white" : "text-zinc-400"
                }`}
              >
                Monthly
              </button>
              <button
                onClick={() => setIsYearly(true)}
                className={`rounded-full px-6 py-2 text-sm font-medium transition-all ${
                  isYearly ? "bg-zinc-600 text-white" : "text-zinc-400"
                }`}
              >
                Yearly
              </button>
            </div>
            {isYearly && <DocumateBadge variant="success">Save 20%</DocumateBadge>}
          </div>

          {/* Pricing Cards */}
          <div className="mt-16 grid grid-cols-1 gap-6 md:grid-cols-2">
            
            {/* Free Plan */}
            <DocumateCard className="text-left">
              <span className="text-sm font-medium text-zinc-400">Free</span>
              <div className="mt-3">
                <span className="text-5xl font-bold text-white">€0</span>
                <span className="ml-1 text-base text-zinc-500">/month</span>
              </div>
              <div className="my-6 border-t border-zinc-800" />
              <ul className="space-y-3">
                {plans[0]?.features?.map((feature, i) => (
                  <li key={i} className="flex items-center gap-2 text-sm text-zinc-400">
                    <Check className="h-3.5 w-3.5 flex-shrink-0 text-zinc-600" />
                    {feature}
                  </li>
                ))}
              </ul>
              <Link href="/register" className="mt-8 block">
                <DocumateButton variant="outline" className="w-full">
                  Get started free
                </DocumateButton>
              </Link>
            </DocumateCard>

            {/* Pro Plan */}
            {proPlan && (
              <div className="relative rounded-2xl border-2 border-white bg-zinc-900 p-6 text-left shadow-[0_0_60px_rgba(255,255,255,0.04)]">
                <DocumateBadge variant="pro" className="absolute -top-3 right-6">
                  Most Popular
                </DocumateBadge>

                <span className="text-sm font-medium text-zinc-400">Pro</span>
                <div className="mt-3">
                  <span className="text-5xl font-bold text-white">
                    {formatPrice(isYearly ? proPlan.price_yearly : proPlan.price_monthly, isYearly)}
                  </span>
                  <span className="ml-1 text-base text-zinc-500">
                    /{isYearly ? "year" : "month"}
                  </span>
                </div>

                <div className="my-6 border-t border-zinc-800" />

                <ul className="space-y-3">
                  {proPlan.features.map((feature, i) => (
                    <li key={i} className="flex items-center gap-2 text-sm text-zinc-400">
                      <Check className="h-3.5 w-3.5 flex-shrink-0 text-emerald-400" />
                      {feature}
                    </li>
                  ))}
                </ul>

                <DocumateButton 
                  className="w-full mt-8"
                  onClick={() => handleSelectPlan(isYearly ? "pro_yearly" : "pro_monthly")}
                >
                  Get Pro →
                </DocumateButton>
              </div>
            )}

            {/* Business Plan */}
            {businessPlan && (
              <DocumateCard className="text-left">
                <span className="text-sm font-medium text-zinc-400">Business</span>
                <div className="mt-3">
                  <span className="text-5xl font-bold text-white">
                    {formatPrice(isYearly ? businessPlan.price_yearly : businessPlan.price_monthly, isYearly)}
                  </span>
                  <span className="ml-1 text-base text-zinc-500">
                    /{isYearly ? "year" : "month"}
                  </span>
                </div>

                <div className="my-6 border-t border-zinc-800" />

                <ul className="space-y-3">
                  {businessPlan.features.map((feature, i) => (
                    <li key={i} className="flex items-center gap-2 text-sm text-zinc-400">
                      <Check className="h-3.5 w-3.5 flex-shrink-0 text-emerald-400" />
                      {feature}
                    </li>
                  ))}
                </ul>

                <DocumateButton 
                  variant="outline" 
                  className="w-full mt-8"
                  onClick={() => handleSelectPlan(isYearly ? "business_yearly" : "business_monthly")}
                >
                  Get Business →
                </DocumateButton>
              </DocumateCard>
            )}
          </div>

          {/* FAQ Section */}
          <div className="mx-auto mt-24 max-w-2xl">
            <h2 className="text-2xl font-semibold text-white">Frequently asked questions</h2>
            <Accordion type="single" collapsible className="mt-8 space-y-2">
              {faqs.map((faq, index) => (
                <AccordionItem
                  key={index}
                  value={`faq-${index}`}
                  className="rounded-xl border border-zinc-800 bg-zinc-900 px-5"
                >
                  <AccordionTrigger className="py-4 text-left text-sm font-medium text-white">
                    {faq.question}
                  </AccordionTrigger>
                  <AccordionContent className="pb-4 text-sm leading-6 text-zinc-400">
                    {faq.answer}
                  </AccordionContent>
                </AccordionItem>
              ))}
            </Accordion>
          </div>

          {/* Money-back Banner */}
          <DocumateCard className="mt-16 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-950">
              <ShieldCheck className="h-6 w-6 text-emerald-400" />
            </div>
            <h3 className="mt-4 text-lg font-semibold text-white">30-day money-back guarantee</h3>
            <p className="mt-2 text-sm text-zinc-500">
              Not satisfied? Get a full refund within 30 days, no questions asked.
            </p>
          </DocumateCard>
        </div>
      </main>

      <Footer />
    </div>
  );
}