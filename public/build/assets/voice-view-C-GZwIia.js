var f=(e,t)=>()=>(t||e((t={exports:{}}).exports,t),t.exports);var w=f((v,c)=>{document.addEventListener("DOMContentLoaded",function(){console.log("[VoiceView] Initializing voice channel main view"),document.querySelector(".voice-channel-page")&&(p(),m())});function p(){document.addEventListener("keydown",function(e){if(!(e.target.tagName==="INPUT"||e.target.tagName==="TEXTAREA")){if((e.key==="m"||e.key==="M")&&typeof window.toggleMute=="function"&&window.toggleMute(),e.key==="d"||e.key==="D"){const t=document.querySelector('.control-btn[title*="Deafen"]');t&&t.click()}if(e.key==="Escape"&&window.dispatchEvent(new CustomEvent("close-modals")),e.key==="t"||e.key==="T"){const t=document.querySelector('.voice-header-btn[title="Toggle Text Chat"]');t&&t.click()}if(e.key==="i"||e.key==="I"){const t=document.querySelector('.voice-header-btn[title="Invite Friends"]');t&&t.click()}}})}function m(){const e=document.querySelector(".voice-text-chat-panel");if(!e)return;let t=!1,i,o;const n=document.createElement("div");n.className="resize-handle",n.style.cssText=`
        position: absolute;
        left: 0;
        top: 0;
        width: 4px;
        height: 100%;
        cursor: ew-resize;
        z-index: 10;
    `,e.prepend(n),n.addEventListener("mousedown",function(s){t=!0,i=s.clientX,o=e.offsetWidth,document.body.style.cursor="ew-resize",s.preventDefault()}),document.addEventListener("mousemove",function(s){if(!t)return;const l=i-s.clientX,u=Math.max(280,Math.min(600,o+l));e.style.width=u+"px"}),document.addEventListener("mouseup",function(){t&&(t=!1,document.body.style.cursor="",localStorage.setItem("voiceTextChatWidth",e.offsetWidth))});const r=localStorage.getItem("voiceTextChatWidth");r&&(e.style.width=r+"px")}function h(e){const t=Math.floor(e/3600),i=Math.floor(e%3600/60),o=e%60;return t>0?`${t}:${i.toString().padStart(2,"0")}:${o.toString().padStart(2,"0")}`:`${i}:${o.toString().padStart(2,"0")}`}function a(e,t="info"){if(typeof window.showNotification=="function"){window.showNotification(e,t);return}const i=document.createElement("div");i.className=`voice-notification voice-notification-${t}`,i.style.cssText=`
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 10000;
        padding: 14px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        font-size: 14px;
        opacity: 0;
        transform: translateX(100px);
        transition: all 0.3s ease;
        max-width: 360px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    `;const o={success:"#43b581",error:"#ed4245",info:"#667eea",warning:"#faa61a"};i.style.backgroundColor=o[t]||o.info,i.textContent=e,document.body.appendChild(i),requestAnimationFrame(()=>{i.style.opacity="1",i.style.transform="translateX(0)"}),setTimeout(()=>{i.style.opacity="0",i.style.transform="translateX(100px)",setTimeout(()=>{i.parentNode&&i.parentNode.removeChild(i)},300)},4e3)}window.showVoiceViewNotification=a;function d(e,t){const i=document.querySelector(`.voice-user-card[data-user-id="${e}"]`);if(!i)return;const o=i.querySelector(".user-avatar-wrapper"),n=i.querySelector(".speaking-ring");t?(i.classList.add("speaking"),o==null||o.classList.add("speaking"),n&&(n.style.display="block")):(i.classList.remove("speaking"),o==null||o.classList.remove("speaking"),n&&(n.style.display="none"))}window.updateUserSpeakingState=d;async function y(e){try{return console.log("[VoiceView] Fetching activities for users:",e),{}}catch(t){return console.error("[VoiceView] Failed to fetch user activities:",t),{}}}typeof c<"u"&&c.exports&&(c.exports={formatDuration:h,showVoiceViewNotification:a,updateUserSpeakingState:d,fetchUserActivities:y})});export default w();
