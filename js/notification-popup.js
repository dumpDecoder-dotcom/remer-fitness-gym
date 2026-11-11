/**
 * 🔔 REMER FITNESS GYM - Universal Auto-Popup Notification System
 * Works for Admin, Staff, Member, Cashier
 */

// Check for new notifications every 5 seconds
setInterval(checkForNewNotifications, 5000);
document.addEventListener('DOMContentLoaded', checkForNewNotifications);

function checkForNewNotifications() {
    fetch('Actions/get_notifications.php?mode=popup')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Notification error:', data.error);
                return;
            }
            
            // Show popup ONLY for new notifications
            if (data.new_notifications > 0 && data.latest_notification) {
                showNotificationPopup(data.latest_notification);
            }
        })
        .catch(error => console.error('Error:', error));
}

function showNotificationPopup(notif) {
    // Remove existing popup
    const existing = document.getElementById('autoNotificationPopup');
    if (existing) existing.remove();
    
    // Get icon based on notification type
    const iconMap = {
        'membership': '⏰',
        'payment': '💰',
        'class_booking': '🏋️‍♀️',
        'class_reminder': '⏰',
        'class_cancellation': '❌',
        'promo': '🏅',
        'attendance': '🏃',
        'system': '📢',
        'login': '✅',
        'default': '🔔'
    };
    const icon = iconMap[notif.type] || iconMap['default'];
    
    // Create popup
    const popup = document.createElement('div');
    popup.id = 'autoNotificationPopup';
    popup.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            animation: fadeIn 0.3s ease;
        " onclick="if(event.target === this) closeAutoPopup()">
            <div style="
                background: white;
                padding: 35px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.4);
                max-width: 550px;
                width: 90%;
                animation: slideDown 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            " onclick="event.stopPropagation()">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="
                        width: 70px;
                        height: 70px;
                        margin: 0 auto 15px;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 35px;
                        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
                    ">${icon}</div>
                    <h3 style="margin: 0; color: #333; font-size: 24px; font-weight: 700;">
                        ${notif.title}
                    </h3>
                </div>
                
                <div style="
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    padding: 25px;
                    border-radius: 15px;
                    margin-bottom: 25px;
                    border-left: 5px solid #667eea;
                    box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
                ">
                    <p style="margin: 0; color: #495057; line-height: 1.7; font-size: 17px;">
                        ${notif.message}
                    </p>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <small style="color: #6c757d; font-size: 13px; display: flex; align-items: center; gap: 5px;">
                        <i class="ri-time-line"></i>
                        <span>${new Date(notif.created_at).toLocaleString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        })}</span>
                    </small>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="closeAutoPopup()" style="
                            background: #6c757d;
                            color: white;
                            border: none;
                            padding: 12px 25px;
                            border-radius: 10px;
                            cursor: pointer;
                            font-size: 15px;
                            font-weight: 600;
                            transition: all 0.3s;
                        " onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
                            Later
                        </button>
                        <button onclick="markAsReadAndClosePopup(${notif.id})" style="
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            color: white;
                            border: none;
                            padding: 12px 30px;
                            border-radius: 10px;
                            cursor: pointer;
                            font-size: 16px;
                            font-weight: 700;
                            transition: all 0.3s;
                            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(102, 126, 234, 0.5)'" 
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(102, 126, 234, 0.4)'">
                            <i class="ri-check-line"></i> Got it!
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(popup);
    
    // Play notification sound
    playNotificationSound();
}

function closeAutoPopup() {
    const popup = document.getElementById('autoNotificationPopup');
    if (popup) {
        popup.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => popup.remove(), 300);
    }
}

function markAsReadAndClosePopup(notifId) {
    fetch('Actions/mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: notifId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAutoPopup();
            
            // Refresh dashboard notification list if function exists
            if (typeof loadNotifications === 'function') {
                loadNotifications();
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function playNotificationSound() {
    try {
        // Simple notification beep
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch(e) {
        console.log('Audio not supported');
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    @keyframes slideDown {
        from {
            transform: translateY(-100px) scale(0.9);
            opacity: 0;
        }
        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);