<script setup lang="ts">
import Footer from '@/components/landing/Footer.vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNavbar } from '@/composables/navbar';
import { dashboard, login, logout, selectUserType } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';

const { isScrolled, activeSection, isMenuOpen, sectionIds, handleClick } =
  useNavbar();
</script>

<template>
  <div>
    <!-- Top Banner Start -->
    <div
      class="mx-auto flex w-full max-w-[1320px] flex-wrap items-center justify-center gap-4 px-3 py-3 text-center md:px-5 xl:justify-between xl:px-4"
    >
      <div class="hidden xl:block">
        <div>
          <img src="@/assets/epinoy/nav_logo.png" class="h-13 w-auto" alt="" />
        </div>
      </div>

      <div class="hidden xl:block">
        <div class="flex items-center gap-8">
          <div class="flex items-center gap-3 text-start">
            <div>
              <img src="@/assets/epinoy/nav_email.png" class="h-7" alt="" />
            </div>
            <div class="leading-none">
              <p class="text-md font-bold text-brand-blue">SEND US AN EMAIL</p>
              <p class="ps-0.5 text-xs text-gray-600">
                info.sample.email@gmail.com
              </p>
            </div>
          </div>
          <div class="flex items-center gap-3 text-start">
            <div>
              <img src="@/assets/epinoy/nav_clock.png" class="h-8" alt="" />
            </div>
            <div class="leading-none">
              <p class="text-md font-bold text-brand-blue">
                AVAILABLE 24 HOURS
              </p>
              <p class="ps-0.5 text-xs text-gray-600">
                Office Mon-Sat : 8am - 5pm
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex w-full gap-2 xl:w-auto xl:gap-3">
        <DropdownMenu v-if="$page.props.auth.user">
          <DropdownMenuTrigger as-child>
            <button
              class="cursor-pointer rounded-md bg-brand-green p-2 text-white transition-all hover:opacity-85"
            >
              <Menu class="h-5 w-7" />
            </button>
          </DropdownMenuTrigger>

          <DropdownMenuContent class="w-40">
            <DropdownMenuItem>
              <Link :href="dashboard()" class="block w-full">Dashboard</Link>
            </DropdownMenuItem>

            <DropdownMenuItem>
              <Link
                :href="logout()"
                method="post"
                as="button"
                class="w-full text-left"
              >
                Logout
              </Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <template v-else>
          <Link
            :href="login()"
            class="flex-1 rounded-md border-2 border-brand-red py-2 text-lg font-bold whitespace-nowrap text-brand-red transition-all hover:opacity-85 xl:border-0 xl:px-5"
          >
            Login
          </Link>
          <Link
            :href="selectUserType()"
            class="flex-1 rounded-md bg-brand-red py-2 text-lg font-bold whitespace-nowrap text-white transition-all hover:opacity-85 xl:px-4"
          >
            Register
          </Link>
        </template>
      </div>
    </div>
    <!-- Top Banner End -->

    <!-- Header Navigation Start -->
    <div>
      <header
        :class="[
          'fixed left-1/2 z-50 flex w-[calc(100%-40px)] max-w-[1320px] -translate-x-1/2 transform items-center justify-center rounded-md px-4 py-2 text-[11px] whitespace-nowrap text-white transition-all duration-300 xl:text-[16px]',
          isScrolled ? 'top-4 bg-brand-blue shadow-brand-shadow' : 'top-22',
        ]"
      >
        <div class="block flex w-full justify-between xl:hidden">
          <div class="block xl:hidden">
            <div>
              <img
                src="@/assets/epinoy/footer-logo.png"
                class="h-10 w-auto xl:h-13"
                alt=""
              />
            </div>
          </div>

          <!-- Burger Button (Mobile Only) -->
          <button
            class="rounded border border-white p-2 text-white focus:outline-none xl:hidden"
            @click="isMenuOpen = !isMenuOpen"
          >
            <svg
              v-if="!isMenuOpen"
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>

            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
        <!-- Navigation Links -->
        <nav
          :class="[
            'absolute top-full left-0 flex w-full flex-col gap-2 overflow-hidden rounded-b-md bg-brand-blue text-xs shadow-md transition-all duration-300 xl:static xl:top-auto xl:w-auto xl:flex-row xl:gap-3 xl:bg-transparent xl:shadow-none',
            isMenuOpen
              ? 'max-h-[600px] bg-brand-blue px-4 py-3'
              : 'max-h-0 opacity-0 xl:max-h-none xl:opacity-100',
          ]"
        >
          <!-- Home Link -->
          <a
            href="#"
            @click="handleClick('home')"
            :class="[
              'rounded-md px-2 py-1 transition-colors duration-300',
              activeSection === 'home'
                ? 'bg-white text-brand-blue'
                : 'hover:bg-white hover:text-brand-blue',
            ]"
          >
            HOME
          </a>

          <!-- Dynamic Section Links -->
          <a
            v-for="id in sectionIds"
            :key="id"
            :href="'#' + id"
            @click="handleClick(id)"
            :class="[
              'rounded-md px-2 py-1 transition-colors duration-300',
              activeSection === id
                ? 'bg-white text-brand-blue'
                : 'hover:bg-white hover:text-brand-blue',
            ]"
          >
            {{
              id === 'about'
                ? 'ABOUT EMSI'
                : id === 'solutions'
                  ? 'SOLUTIONS'
                  : id === 'franchise'
                    ? 'FRANCHISE'
                    : id === 'services'
                      ? 'SERVICES'
                      : id === 'technology'
                        ? 'TECHNOLOGY'
                        : id === 'sustainability'
                          ? 'SUSTAINABILITY'
                          : id === 'partners'
                            ? 'PARTNERS'
                            : id === 'news'
                              ? 'NEWS & UPDATES'
                              : id === 'careers'
                                ? 'CAREERS'
                                : id === 'testimonials'
                                  ? 'TESTIMONIALS'
                                  : id === 'contact'
                                    ? 'CONTACT US'
                                    : id
            }}
          </a>
          <div>
            <hr />

            <div class="block pt-2 text-white xl:hidden">
              <div
                class="flex flex-wrap items-center justify-between gap-3 py-2"
              >
                <div class="flex items-center gap-3 text-start">
                  <div>
                    <img
                      src="@/assets/epinoy/nav_email.png"
                      class="h-7"
                      alt=""
                    />
                  </div>
                  <div class="leading-none">
                    <p class="text-md font-bold">SEND US AN EMAIL</p>
                    <p class="ps-0.5 text-xs">info.sample.email@gmail.com</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 text-start">
                  <div>
                    <img
                      src="@/assets/epinoy/nav_clock.png"
                      class="h-8"
                      alt=""
                    />
                  </div>
                  <div class="leading-none">
                    <p class="text-md font-bold">AVAILABLE 24 HOURS</p>
                    <p class="ps-0.5 text-xs">Office Mon-Sat : 8am - 5pm</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </nav>
      </header>
    </div>
    <!-- Header Navigation End -->

    <!-- Main Start -->
    <main>
      <slot />
    </main>
    <!-- Main End -->

    <!-- Footer Start -->
    <Footer />
    <!-- Footer End -->
  </div>
</template>

<style scoped>
@media only screen and (max-width: 1280px) {
  header {
    background: #16419d !important;
  }
}
</style>
