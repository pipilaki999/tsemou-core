document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.tsemou-file-nav button').forEach(function(btn){
    btn.addEventListener('click', function(){
      const root = btn.closest('.tsemou-file-os');
      root.querySelectorAll('.tsemou-file-nav button').forEach(b => b.classList.remove('active'));
      root.querySelectorAll('.tsemou-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      root.querySelector('#tsemou-panel-' + btn.dataset.panel).classList.add('active');
    });
  });
});
