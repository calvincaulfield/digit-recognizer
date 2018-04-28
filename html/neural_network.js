function matrixVectorMultiplication(matrix, vector) {
	function dotProduct(vector1, vector2) {
		if (vector1.length !== vector2.length) {
			alert('matrixVectorMultiplication error');
		}
		let sum = 0.0;
		for (let i = 0; i < vector1.length; i++) {
			sum += 	vector1[i] * vector2[i];	
		}
		return sum;
	}
	
	let result = [];
	for (let i = 0; i < matrix.length; i++) {
		result.push(dotProduct(matrix[i], vector));	
	}
	return result;
}

function vectorAddition(vector1, vector2) {
	if (vector1.length !== vector2.length) {
		alert('vectorAddition error');
	}
	let result = [];
	for (let i = 0; i < vector1.length; i++) {
		result.push(vector1[i] + vector2[i]);	
	}
	return result;
}

function runNetwork(network, input, activation) {
	let currOutput = input;
	for (let i = 0; i < network.length; i++) {
		let currLayer = network[i];
		currOutput = matrixVectorMultiplication(currLayer.weights, currOutput);
		currOutput = vectorAddition(currOutput, currLayer.biases);
		currOutput = currOutput.map(activation);
	}
	return currOutput;
}