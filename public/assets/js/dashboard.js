// Initialize Chart
const ctx = document.getElementById("myChart").getContext("2d");
new Chart(ctx, {
    type: "line",
    data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
        datasets: [
            {
                label: "Monthly Performance",
                data: [400, 300, 600, 800, 500, 700],
                fill: true,
                borderColor: "#4299e1",
                backgroundColor: "rgba(66, 153, 225, 0.1)",
                tension: 0.4,
                borderWidth: 2,
                pointBackgroundColor: "#4299e1",
                pointBorderColor: "#fff",
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [2, 2],
                },
            },
            x: {
                grid: {
                    display: false,
                },
            },
        },
    },
});

// Add click functionality to checkboxes
document.querySelectorAll(".checkbox").forEach((checkbox) => {
    checkbox.addEventListener("click", () => {
        checkbox.classList.toggle("done");
    });
});
