/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                plum: '#3D1B3D',
                'plum-light': '#5A2E5A',
                orange: '#E2703A',
                rose: '#B96562',
                gold: '#C9972B',
                cream: '#FFFDFB',
                'blush-1': '#FBEFEA',
                'blush-2': '#F3D8DE',
                ink: '#3A2E30',
                muted: '#8A7B7E',
                line: '#EBDDD8',
            },
        },
    },
    plugins: [],
};

