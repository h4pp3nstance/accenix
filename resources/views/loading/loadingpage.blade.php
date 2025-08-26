<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #0d304d;
        }

        .loading-area {
            display: grid;
            place-items: center;
            height: 100vh;
        }

        .loader div {
            height: 30px;
            width: 30px;
            border-radius: 50%;
            transform: scale(0);
            animation: animate 1.5s ease-in-out infinite;
            display: inline-block;
            margin: .5rem;
        }

        .loader div:nth-child(0) {
            animation-delay: 0s;
        }

        .loader div:nth-child(1) {
            animation-delay: 0.2s;
        }

        .loader div:nth-child(2) {
            animation-delay: 0.4s;
        }

        .loader div:nth-child(3) {
            animation-delay: 0.6s;
        }

        .loader div:nth-child(4) {
            animation-delay: 0.8s;
        }

        .loader div:nth-child(5) {
            animation-delay: 1s;
        }

        .loader div:nth-child(6) {
            animation-delay: 1.2s;
        }

        .loader div:nth-child(7) {
            animation-delay: 1.4s;
        }

        @keyframes animate {

            0%,
            100% {
                transform: scale(0.2);
                background-color: #bd0036;
            }

            40% {
                transform: scale(1);
                background-color: #f25330;
            }

            50% {
                transform: scale(1);
                background-color: #f2b900;
            }
        }
    </style>
</head>

<body>
    <div class="loading-area">
        <div class="loader">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
</body>

</html>
