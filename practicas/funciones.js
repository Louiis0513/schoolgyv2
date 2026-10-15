let num1=37;
let num2=89;
let suma1=num1+num2;
console.log(suma1);

let num3=78;
let num4=35;
let suma2=num3+num4;
console.log(suma2);


function suma (num1,num2){
    let suma= num1+num2;
    return suma;
}
console.log(suma(5,15));
console.log(suma(10,23));
console.log(suma(56,15));
console.log(suma(78,95));
console.log(suma(45,56));
console.log(suma(79,52));
console.log(suma(12,54));
console.log(suma(65,28));
console.log(suma(49,79));
console.log(suma(50,23));

function resta (num1,num2){
    let resta= num1-num2;
    return resta;
}
console.log(resta(5,15));
console.log(resta(59,87));

function email (correoelectronico){
    let email= correoelectronico;
    return "el correo electronico es:"+ correoelectronico;
}
console.log(email("meilogan@gmail.com"));