<?php
// Only show if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    return;
}
$__uid = (int)($_SESSION['user_id'] ?? $_SESSION['id']); // expose to JS
?>
<style>
/* Notification Bell Button */
.notification-bell-container{position:relative;display:inline-block}
.notification-bell-btn{background:transparent;border:none;color:white;font-size:24px;cursor:pointer;position:relative;padding:8px;transition:all .3s ease}
.notification-bell-btn:hover{transform:scale(1.1)}
.notification-badge{position:absolute;top:0;right:0;background:#ff4444;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;min-width:18px;text-align:center;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.1)}}

/* Auto-dismiss Toast */
.notification-toast{position:fixed;top:20px;right:20px;min-width:320px;max-width:400px;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.25);z-index:10000;animation:slideInRight .4s cubic-bezier(.68,-.55,.265,1.55);display:flex;align-items:center;padding:16px;gap:14px;border-left:4px solid #667eea;cursor:pointer;transition:transform .2s,box-shadow .2s}
.notification-toast:hover{transform:translateX(-4px);box-shadow:0 12px 40px rgba(0,0,0,.3)}
@keyframes slideInRight{from{transform:translateX(450px);opacity:0}to{transform:translateX(0);opacity:1}}
.notification-toast-icon{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.notification-toast-content{flex:1;min-width:0}
.notification-toast-title{font-weight:700;margin-bottom:4px;font-size:14px;color:#1a202c}
.notification-toast-message {
  font-size: 13px;
  color: #4a5568;
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2; /* for Chrome, Safari */
  line-clamp: 2;         /* for modern browsers (fixes the warning) */
}
.notification-toast-close{background:transparent;border:none;color:#a0aec0;cursor:pointer;font-size:20px;padding:4px;flex-shrink:0;transition:color .2s}
.notification-toast-close:hover{color:#2d3748}

/* Member type icon styles */
.membership-expiry-icon{background:linear-gradient(135deg,#FF6B35 0%,#FF8E53 100%);color:#fff}
.payment-due-icon{background:linear-gradient(135deg,#DC2626 0%,#EF4444 100%);color:#fff}
.class-booking-icon{background:linear-gradient(135deg,#10B981 0%,#34D399 100%);color:#fff}
.class-reminder-icon{background:linear-gradient(135deg,#3B82F6 0%,#60A5FA 100%);color:#fff}
.renewal-icon{background:linear-gradient(135deg,#059669 0%,#10B981 100%);color:#fff}
.promo-icon{background:linear-gradient(135deg,#A855F7 0%,#C084FC 100%);color:#fff}
.class-reschedule-icon{background:linear-gradient(135deg,#F59E0B 0%,#FBBF24 100%);color:#fff}
.check-in-icon{background:linear-gradient(135deg,#14B8A6 0%,#2DD4BF 100%);color:#fff}
.announcement-icon{background:linear-gradient(135deg,#fa709a 0%,#fee140 100%);color:#fff}

/* Responsive */
@media (max-width:768px){
  .notification-toast{min-width:calc(100vw - 40px);max-width:calc(100vw - 40px);left:20px;right:20px}
}
</style>

<!-- Notification Toast Container -->
<div id="notificationToastContainer"></div>

<script>
// ---------- per-user namespaced storage ----------
const CURRENT_USER_ID = <?php echo $__uid; ?>;
const SHOWN_KEY = `shownNotifications_${CURRENT_USER_ID}`;
let shownNotifications = JSON.parse(localStorage.getItem(SHOWN_KEY) || '[]');

// Icon mapping for member types
const notificationIcons = {
  membership_expiry:{class:'membership-expiry-icon',icon:'ri-time-line'},
  payment_due:{class:'payment-due-icon',icon:'ri-money-dollar-circle-line'},
  class_booking:{class:'class-booking-icon',icon:'ri-checkbox-circle-fill'},
  class_reminder:{class:'class-reminder-icon',icon:'ri-calendar-event-line'},
  renewal_confirmation:{class:'renewal-icon',icon:'ri-check-double-line'},
  promo:{class:'promo-icon',icon:'ri-gift-fill'},
  class_reschedule:{class:'class-reschedule-icon',icon:'ri-alert-line'},
  class_cancelled:{class:'class-reschedule-icon',icon:'ri-close-circle-line'},
  check_in:{class:'check-in-icon',icon:'ri-login-circle-line'},
  announcement:{class:'announcement-icon',icon:'ri-megaphone-fill'},
  product:{class:'promo-icon',icon:'ri-shopping-bag-fill'},
  class_schedule:{class:'class-reminder-icon',icon:'ri-calendar-line'},
  payment_success:{class:'renewal-icon',icon:'ri-check-double-line'},
  default:{class:'announcement-icon',icon:'ri-notification-4-fill'}
};

function checkNewNotifications(){
  fetch('Actions/get_new_notifications.php')
    .then(r=>r.json())
    .then(data=>{
      if(!data || !data.success || !Array.isArray(data.notifications)) return;
      data.notifications.forEach((n, i)=>{
        if(!shownNotifications.includes(n.id)){
          setTimeout(()=>{
            showNotificationToast(n);
            shownNotifications.push(n.id);
            if(shownNotifications.length>100){shownNotifications=shownNotifications.slice(-50);}
            localStorage.setItem(SHOWN_KEY, JSON.stringify(shownNotifications));
          }, i*3500);
        }
      });
      updateNotificationBellCount();
    })
    .catch(err=>console.error('Error checking notifications:',err));
}

function showNotificationToast(n){
  const iconData = notificationIcons[n.type] || notificationIcons.default;
  const toast = document.createElement('div');
  toast.className='notification-toast';
  toast.innerHTML = `
    <div class="notification-toast-icon ${iconData.class}">
      <i class="${iconData.icon}"></i>
    </div>
    <div class="notification-toast-content">
      <div class="notification-toast-title">${escapeHtml(n.title || '')}</div>
      <div class="notification-toast-message">${escapeHtml(n.message || '')}</div>
    </div>
    <button class="notification-toast-close" onclick="closeNotificationToast(this,event)">
      <i class="ri-close-line"></i>
    </button>
  `;
  toast.onclick = (e)=>{
    if(!e.target.closest('.notification-toast-close')){ viewNotification(n.id); }
  };
  document.getElementById('notificationToastContainer').appendChild(toast);
  playNotificationSound();
  setTimeout(()=>{
    if(toast.parentElement){ closeNotificationToast(toast.querySelector('.notification-toast-close'), null); }
  }, 8000);
}

function closeNotificationToast(btn, evt){
  if(evt) evt.stopPropagation();
  const toast = btn.closest('.notification-toast');
  if(!toast) return;
  toast.style.animation = 'slideInRight .3s ease-out reverse';
  setTimeout(()=>{ if(toast.parentElement) toast.remove(); }, 300);
}

function viewNotification(id){
  fetch('Actions/mark_notification_read.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`notification_id=${encodeURIComponent(id)}`
  }).finally(()=>{
    // navigate to your notifications page or detail
    window.location.href = 'notifications.php?id='+encodeURIComponent(id);
  });
}

function updateNotificationBellCount(){
  // Expect an endpoint that returns {count: <int>}
  fetch('Actions/get_unread_count.php')
    .then(r=>r.json())
    .then(data=>{
      const badge = document.querySelector('.notification-badge');
      if(!badge) return;
      const c = Number(data?.count||0);
      if(c>0){ badge.textContent = c>99?'99+':c; badge.style.display='flex'; }
      else { badge.style.display='none'; }
    })
    .catch(err=>console.error('Error updating notification count:',err));
}

function playNotificationSound(){
  const audio = new Audio('Assets/notification-sound.mp3');
  audio.volume = .3;
  audio.play().catch(()=>{ /* autoplay may be blocked */ });
}

function escapeHtml(t){
  const d=document.createElement('div'); d.textContent=t??''; return d.innerHTML;
}

// Kickoff
window.addEventListener('DOMContentLoaded', ()=>{
  checkNewNotifications();
  updateNotificationBellCount();
});

// Optional: bell click -> notifications page
document.addEventListener('DOMContentLoaded', ()=>{
  const bellBtn = document.getElementById('notificationBell');
  if(bellBtn){
    bellBtn.addEventListener('click', ()=>{ window.location.href='notifications.php'; });
  }
});

// Refresh badge when tab becomes visible
document.addEventListener('visibilitychange', ()=>{
  if(!document.hidden){ updateNotificationBellCount(); }
});
</script>
