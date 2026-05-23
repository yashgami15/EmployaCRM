        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
<script src="js/column-visibility.js"></script>
<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $scriptTag): ?>
        <?= $scriptTag ?>
    <?php endforeach; ?>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const unreadCount = <?= (int) ($unreadCount ?? 0) ?>;
    const lastCount = parseInt(localStorage.getItem('notification_count') || '0', 10);
    
    if (unreadCount > lastCount) {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            oscillator.start();
            setTimeout(() => oscillator.stop(), 250);
        } catch(e) { console.log('Audio disabled'); }
    }
    localStorage.setItem('notification_count', unreadCount);
});
</script>
</body>
</html>
