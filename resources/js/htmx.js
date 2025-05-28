// Disable ranking type select field until a round is selected
document.addEventListener('DOMContentLoaded', function() {
    const roundSelect = document.getElementById('round_id');
    const rankingTypeSelect = document.getElementById('ranking_type_id');

    // Function to update the disabled state
    function updateRankingTypeSelectState() {
        if (roundSelect && rankingTypeSelect) {
            rankingTypeSelect.disabled = !roundSelect.value;
        }
    }

    // Initial state
    updateRankingTypeSelectState();

    // Listen for changes on the round select
    if (roundSelect) {
        roundSelect.addEventListener('change', updateRankingTypeSelectState);
    }

    // Listen for HTMX events to handle dynamic updates
    document.body.addEventListener('htmx:afterSwap', function(event) {
        // If the target is the ranking type select or its container
        if (event.detail.target.id === 'ranking_type_id' ||
            event.detail.target.querySelector('#ranking_type_id')) {
            // Get the updated elements
            const updatedRoundSelect = document.getElementById('round_id');
            const updatedRankingTypeSelect = document.getElementById('ranking_type_id');

            // Update the state
            if (updatedRoundSelect && updatedRankingTypeSelect) {
                updatedRankingTypeSelect.disabled = !updatedRoundSelect.value;
            }
        }
    });
});
