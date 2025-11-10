<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
</script>

<template>
  <Head title="Terms of Service - ServeWise" />

  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-10">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-xl md:text-2xl font-bold text-gray-900">ServeWise</h1>
          <Link href="/register">
            <Button variant="ghost" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Registration
            </Button>
          </Link>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
      <div class="bg-white rounded-lg shadow-sm border p-6 md:p-8">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">Terms of Service</h1>
        <p class="text-sm text-gray-500 mb-8">Last updated: {{ new Date().toLocaleDateString() }}</p>

        <div class="prose prose-sm md:prose-base max-w-none space-y-6">
          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">1. Acceptance of Terms</h2>
            <p class="text-gray-700 leading-relaxed">
              By accessing and using ServeWise ("the Service"), you accept and agree to be bound by the terms and provision of this agreement.
              If you do not agree to these Terms of Service, please do not use the Service.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">2. Description of Service</h2>
            <p class="text-gray-700 leading-relaxed">
              ServeWise is a comprehensive restaurant management system that provides tools for managing restaurant operations,
              including but not limited to inventory management, order processing, menu planning, employee management, and supplier coordination.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">3. User Accounts</h2>
            <p class="text-gray-700 leading-relaxed mb-3">
              To use the Service, you must create an account. You agree to:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
              <li>Provide accurate, current, and complete information during registration</li>
              <li>Maintain and promptly update your account information</li>
              <li>Maintain the security of your password and account</li>
              <li>Accept responsibility for all activities that occur under your account</li>
              <li>Notify us immediately of any unauthorized use of your account</li>
            </ul>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">4. Subscription and Payment</h2>
            <p class="text-gray-700 leading-relaxed mb-3">
              ServeWise operates on a subscription-based model:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
              <li>Subscription fees are charged according to the plan you select</li>
              <li>Payment is required before accessing premium features</li>
              <li>Subscriptions automatically renew unless cancelled</li>
              <li>Refunds are subject to our refund policy</li>
              <li>We reserve the right to change subscription fees with notice</li>
            </ul>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">5. Acceptable Use</h2>
            <p class="text-gray-700 leading-relaxed mb-3">
              You agree not to:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
              <li>Use the Service for any illegal purpose or in violation of any laws</li>
              <li>Attempt to gain unauthorized access to the Service or related systems</li>
              <li>Interfere with or disrupt the Service or servers</li>
              <li>Upload malicious code, viruses, or harmful content</li>
              <li>Impersonate another person or entity</li>
              <li>Harvest or collect information about other users</li>
            </ul>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">6. Intellectual Property</h2>
            <p class="text-gray-700 leading-relaxed">
              All content, features, and functionality of the Service, including but not limited to text, graphics, logos, and software,
              are owned by ServeWise or its licensors and are protected by intellectual property laws. You may not copy, modify,
              distribute, or create derivative works without explicit permission.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">7. Data Ownership and Usage</h2>
            <p class="text-gray-700 leading-relaxed">
              You retain ownership of all data you input into the Service. By using the Service, you grant us a license to store,
              process, and display your data solely for the purpose of providing the Service to you. We will not sell or share your
              data with third parties except as described in our Privacy Policy.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">8. Termination</h2>
            <p class="text-gray-700 leading-relaxed">
              We reserve the right to suspend or terminate your account and access to the Service at any time, with or without notice,
              for conduct that we believe violates these Terms of Service or is harmful to other users, us, or third parties, or for any other reason.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">9. Disclaimer of Warranties</h2>
            <p class="text-gray-700 leading-relaxed">
              THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED.
              WE DO NOT WARRANT THAT THE SERVICE WILL BE UNINTERRUPTED, ERROR-FREE, OR SECURE.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">10. Limitation of Liability</h2>
            <p class="text-gray-700 leading-relaxed">
              TO THE MAXIMUM EXTENT PERMITTED BY LAW, SERVEWISE SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL,
              CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS OR REVENUES, WHETHER INCURRED DIRECTLY OR INDIRECTLY,
              OR ANY LOSS OF DATA, USE, OR OTHER INTANGIBLE LOSSES.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">11. Changes to Terms</h2>
            <p class="text-gray-700 leading-relaxed">
              We reserve the right to modify these Terms of Service at any time. We will notify users of any material changes via email
              or through the Service. Your continued use of the Service after such modifications constitutes acceptance of the updated terms.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">12. Contact Information</h2>
            <p class="text-gray-700 leading-relaxed">
              If you have any questions about these Terms of Service, please contact us at:
              <br><br>
              <strong>Email:</strong> support@servewise.com<br>
              <strong>Website:</strong> www.servewise.com
            </p>
          </section>
        </div>

        <div class="mt-8 pt-6 border-t">
          <Link href="/register">
            <Button variant="outline">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Registration
            </Button>
          </Link>
        </div>
      </div>
    </main>
  </div>
</template>
