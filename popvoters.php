<script>
	var table=0;
	var hlvotno=0;
				
	function getVoters(value){	
		table="mce";
		var url = "ajax/getvoters.php?value="+encodeURIComponent(value)+"&id="+hlvotno+"&table="+table;
		fetch(url)
			.then(response => response.text())
			.then(data => {
				getID("query_voters").innerHTML = data;
			})
			.catch(err => console.error("Fetch error in getVoters:", err));
	}
				
	function add(){
		getVoters('');
		var addModal = new bootstrap.Modal(document.getElementById('votersModal'));
		addModal.show();
	}
</script>

<!-- Voters Modal -->
<div class="modal fade" id="votersModal" tabindex="-1" aria-labelledby="votersModalLabel" aria-hidden="true" style="margin-top:50px;height:900px">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: var(--radius-md);">
			<div class="modal-header bg-danger text-white">
				<h5 class="modal-title" id="votersModalLabel"><i class="fa-solid fa-user-plus"></i> Select Voter to Add</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<input type="text" class="form-control" placeholder="Type a keyword to search..." onkeyup="getVoters(this.value)" />
				</div>
				<div id="query_voters"></div>
			</div>
		</div>
	</div>
</div>