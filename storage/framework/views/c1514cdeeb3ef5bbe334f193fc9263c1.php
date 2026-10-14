<header class="app-topbar">
     <div class="container-fluid">
          <div class="navbar-header">
               <div class="d-flex align-items-center gap-2">
                    <!-- Menu Toggle Button -->
                    <div class="topbar-item">
                         <button type="button" class="button-toggle-menu topbar-button">
                              <iconify-icon icon="solar:hamburger-menu-outline"
                                   class="fs-24 align-middle"></iconify-icon>
                         </button>
                    </div>

                    <!-- App Search-->
                    
               </div>

               <div class="d-flex align-items-center gap-2">
                    <!-- Theme Color (Light/Dark) -->
                    <div class="topbar-item">
                         <button type="button" class="topbar-button" id="light-dark-mode">
                              <iconify-icon icon="solar:moon-outline"
                                   class="fs-22 align-middle light-mode"></iconify-icon>
                              <iconify-icon icon="solar:sun-2-outline"
                                   class="fs-22 align-middle dark-mode"></iconify-icon>
                         </button>
                    </div>

                    <!-- Notification -->
                    

                    <!-- User -->
                    <div class="dropdown topbar-item">
                         <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                              aria-haspopup="true" aria-expanded="false">
                              <span class="d-flex align-items-center">
                                   <img class="rounded-circle" width="32" src="<?php echo e(Auth::user()?->image_path ? asset(Auth::user()->image_path) : asset('images/users/avatar-6.jpg')); ?>"
                                        alt="avatar-3">
                              </span>
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <!-- item-->
                              <h6 class="dropdown-header">Welcome!</h6>

                              <a class="dropdown-item" href="<?php echo e(route('admin.profile.edit')); ?>">
                                   <iconify-icon icon="solar:user-outline"
                                        class="align-middle me-2 fs-18"></iconify-icon><span class="align-middle">My
                                        Account</span>
                              </a>

                         
                              <a class="dropdown-item" href="<?php echo e(route ('second' , ['auth','lock-screen'])); ?>">
                                   <iconify-icon icon="solar:lock-keyhole-outline"
                                        class="align-middle me-2 fs-18"></iconify-icon><span class="align-middle">Lock
                                        screen</span>
                              </a>

                              <div class="dropdown-divider my-1"></div>

                              <a class="dropdown-item text-danger" href="<?php echo e(route ('second' , ['auth','signin'])); ?>">
                                   <iconify-icon icon="solar:logout-3-outline"
                                        class="align-middle me-2 fs-18"></iconify-icon><span
                                        class="align-middle">Logout</span>
                              </a>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</header><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/layouts/partials/topbar.blade.php ENDPATH**/ ?>