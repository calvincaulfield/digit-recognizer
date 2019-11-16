#ifndef NEURAL_NETWORK_H
#define NEURAL_NETWORK_H

#include "matrix.h"
#include <vector>

namespace digit_recognizer
{

using std::vector;

class neural_network
{
public:
	neural_network(size_t numInput, size_t numOutput, vector<size_t> hiddenLayers, double learningRate);
	vector<double> train(size_t numIter,
						 vector<std::pair<vector<double>, vector<double>>> trainData,
						 vector<std::pair<vector<double>, vector<double>>> testData);
	// Return succees rate
	double test(vector<std::pair<vector<double>, vector<double>>> testData) const;

private:
	class layer
	{
	public:
		layer(); // dummy
		layer(size_t numInput, size_t numOutput, bool isOutputLayer);
		layer &operator=(layer const &);

	public:
		friend class neural_network;

	public:
		size_t getNumInput() const;
		size_t getNumOutput() const;
		vector<double> getOutput() const;
		void clearGradient() const;
		void randomize();
		// Return outputs.
		vector<double> runNetwork(vector<double> const &previousLayerOutput) const;
		// Output values must have been calculated already.
		vector<double> getLayerMultiplier() const;
		// As there will be multiple test data, we have to sum gradient for each.
		// Calculate gradient for current previous and next layer setting, and accumulate it.
		void addGradient(vector<double> const &previousLayerOutput, vector<double> const &nextLayerMultiplier) const;
		// w <- w + gradient * multiplier
		void adjustVariablesWithMultiplier(double multipler);

	private:
		// Sets errors_ vector. For hidden layers.
		void calculateErrorsForHiddenLayer(vector<double> const &nextLayerMultiplier) const;
		// For output layer.
		void calculateErrorsForOutputLayer(vector<double> const &outputData) const;
		// Return matrix product of output and weights
	private:
		double getSigmoid(double) const;
		double getDerivativeOfSigmoid(double) const;

	private:
		bool isOutputLayer_;

	private:
		matrix<double> weights_;
		vector<double> biases_;

	private:
		mutable vector<double> z_;		 // z = wx + bias
		mutable vector<double> outputs_; // output = sigmoid(z)
		mutable vector<double> errors_;
		mutable matrix<double> accumulated_gradient_weights_;
		mutable vector<double> accumulated_gradient_biases_;
	};

private:
	vector<double> runNetwork(vector<double> const &input) const;
	double learnFromData(vector<std::pair<vector<double>, vector<double>>> const &trainData);
	double learnFromData(vector<double> const &input, vector<double> const &output); // sum of square
	void randomize();
	void saveJson() const;
	vector<vector<double>> convertMatrixToVetor(matrix<double> const &) const;

private:
	vector<layer> layers_; // last element is output
	double learningRate_;
};

} // namespace digit_recognizer

#endif
